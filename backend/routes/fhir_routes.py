"""
HL7 FHIR R4 Integration
RESTful FHIR API implementation
"""
from flask import Blueprint, request, jsonify, g
from datetime import datetime, date
import json
import uuid

fhir_bp = Blueprint('fhir', __name__, url_prefix='/api/fhir/r4')


def create_fhir_bundle(resources, bundle_type='searchset', total=None):
    """Create a FHIR Bundle resource"""
    bundle = {
        'resourceType': 'Bundle',
        'id': str(uuid.uuid4()),
        'type': bundle_type,
        'timestamp': datetime.utcnow().isoformat() + 'Z',
        'entry': [{'resource': r, 'fullUrl': f"urn:uuid:{r.get('id', uuid.uuid4())}"} for r in resources]
    }
    if total is not None:
        bundle['total'] = total
    return bundle


def create_operation_outcome(severity, code, diagnostics):
    """Create a FHIR OperationOutcome resource"""
    return {
        'resourceType': 'OperationOutcome',
        'issue': [{
            'severity': severity,
            'code': code,
            'diagnostics': diagnostics
        }]
    }


class FHIRPatientConverter:
    """Convert between internal Patient model and FHIR Patient resource"""
    
    @staticmethod
    def to_fhir(patient, include_sensitive=True):
        """Convert internal patient to FHIR Patient resource"""
        resource = {
            'resourceType': 'Patient',
            'id': str(patient.id),
            'meta': {
                'versionId': '1',
                'lastUpdated': patient.updated_at.isoformat() + 'Z' if patient.updated_at else datetime.utcnow().isoformat() + 'Z'
            },
            'identifier': [
                {
                    'use': 'usual',
                    'type': {
                        'coding': [{
                            'system': 'http://terminology.hl7.org/CodeSystem/v2-0203',
                            'code': 'MR',
                            'display': 'Medical Record Number'
                        }]
                    },
                    'system': 'urn:oid:1.2.3.4.5.6.7.8.9',
                    'value': patient.mrn
                }
            ],
            'active': True,
            'name': [{
                'use': 'official',
                'family': patient.last_name,
                'given': [patient.first_name]
            }],
            'telecom': [],
            'gender': patient.gender.lower() if patient.gender else 'unknown',
            'birthDate': patient.date_of_birth.isoformat() if patient.date_of_birth else None,
            'address': []
        }
        
        # Add middle name if present
        if hasattr(patient, 'middle_name') and patient.middle_name:
            resource['name'][0]['given'].append(patient.middle_name)
        
        # Add SSN identifier (if allowed and present)
        if include_sensitive and hasattr(patient, 'ssn') and patient.ssn:
            resource['identifier'].append({
                'use': 'official',
                'type': {
                    'coding': [{
                        'system': 'http://terminology.hl7.org/CodeSystem/v2-0203',
                        'code': 'SS',
                        'display': 'Social Security Number'
                    }]
                },
                'system': 'http://hl7.org/fhir/sid/us-ssn',
                'value': patient.ssn
            })
        
        # Add contact info
        if hasattr(patient, 'phone') and patient.phone:
            resource['telecom'].append({
                'system': 'phone',
                'value': patient.phone,
                'use': 'home'
            })
        
        if hasattr(patient, 'email') and patient.email:
            resource['telecom'].append({
                'system': 'email',
                'value': patient.email
            })
        
        # Add address
        if hasattr(patient, 'address_line1'):
            address = {
                'use': 'home',
                'type': 'physical',
                'line': [patient.address_line1] if patient.address_line1 else []
            }
            if hasattr(patient, 'address_line2') and patient.address_line2:
                address['line'].append(patient.address_line2)
            if hasattr(patient, 'city') and patient.city:
                address['city'] = patient.city
            if hasattr(patient, 'state') and patient.state:
                address['state'] = patient.state
            if hasattr(patient, 'zip_code') and patient.zip_code:
                address['postalCode'] = patient.zip_code
            if address['line']:
                resource['address'].append(address)
        
        # Add extensions for race/ethnicity (US Core)
        extensions = []
        if hasattr(patient, 'race') and patient.race:
            extensions.append({
                'url': 'http://hl7.org/fhir/us/core/StructureDefinition/us-core-race',
                'extension': [{
                    'url': 'text',
                    'valueString': patient.race
                }]
            })
        
        if hasattr(patient, 'ethnicity') and patient.ethnicity:
            extensions.append({
                'url': 'http://hl7.org/fhir/us/core/StructureDefinition/us-core-ethnicity',
                'extension': [{
                    'url': 'text',
                    'valueString': patient.ethnicity
                }]
            })
        
        if extensions:
            resource['extension'] = extensions
        
        return resource
    
    @staticmethod
    def from_fhir(fhir_resource):
        """Convert FHIR Patient resource to internal model data"""
        data = {}
        
        # Extract name
        if fhir_resource.get('name'):
            name = fhir_resource['name'][0]
            data['last_name'] = name.get('family')
            if name.get('given'):
                data['first_name'] = name['given'][0]
                if len(name['given']) > 1:
                    data['middle_name'] = name['given'][1]
        
        # Extract identifiers
        for identifier in fhir_resource.get('identifier', []):
            id_type = identifier.get('type', {}).get('coding', [{}])[0].get('code')
            if id_type == 'MR':
                data['mrn'] = identifier.get('value')
            elif id_type == 'SS':
                data['ssn'] = identifier.get('value')
        
        # Extract demographics
        data['gender'] = fhir_resource.get('gender', '').capitalize()
        if fhir_resource.get('birthDate'):
            data['date_of_birth'] = datetime.strptime(fhir_resource['birthDate'], '%Y-%m-%d').date()
        
        # Extract contact info
        for telecom in fhir_resource.get('telecom', []):
            if telecom.get('system') == 'phone':
                data['phone'] = telecom.get('value')
            elif telecom.get('system') == 'email':
                data['email'] = telecom.get('value')
        
        # Extract address
        if fhir_resource.get('address'):
            addr = fhir_resource['address'][0]
            if addr.get('line'):
                data['address_line1'] = addr['line'][0]
                if len(addr['line']) > 1:
                    data['address_line2'] = addr['line'][1]
            data['city'] = addr.get('city')
            data['state'] = addr.get('state')
            data['zip_code'] = addr.get('postalCode')
        
        return data


class FHIREncounterConverter:
    """Convert between internal Encounter model and FHIR Encounter resource"""
    
    @staticmethod
    def to_fhir(encounter):
        """Convert internal encounter to FHIR Encounter resource"""
        resource = {
            'resourceType': 'Encounter',
            'id': str(encounter.id),
            'meta': {
                'lastUpdated': encounter.updated_at.isoformat() + 'Z' if encounter.updated_at else datetime.utcnow().isoformat() + 'Z'
            },
            'identifier': [{
                'use': 'usual',
                'value': encounter.visit_number if hasattr(encounter, 'visit_number') else str(encounter.id)
            }],
            'status': FHIREncounterConverter._map_status(encounter.status),
            'class': FHIREncounterConverter._map_class(encounter.patient_class if hasattr(encounter, 'patient_class') else 'inpatient'),
            'subject': {
                'reference': f'Patient/{encounter.patient_id}'
            },
            'period': {
                'start': encounter.admission_date.isoformat() + 'Z' if encounter.admission_date else None
            }
        }
        
        if hasattr(encounter, 'discharge_date') and encounter.discharge_date:
            resource['period']['end'] = encounter.discharge_date.isoformat() + 'Z'
        
        # Add reason for visit
        if hasattr(encounter, 'chief_complaint') and encounter.chief_complaint:
            resource['reasonCode'] = [{
                'text': encounter.chief_complaint
            }]
        
        # Add location
        if hasattr(encounter, 'location') and encounter.location:
            resource['location'] = [{
                'location': {
                    'display': encounter.location
                },
                'status': 'active'
            }]
        
        # Add service provider
        if hasattr(encounter, 'facility') and encounter.facility:
            resource['serviceProvider'] = {
                'display': encounter.facility
            }
        
        return resource
    
    @staticmethod
    def _map_status(internal_status):
        """Map internal status to FHIR status"""
        status_map = {
            'active': 'in-progress',
            'in_progress': 'in-progress',
            'admitted': 'in-progress',
            'discharged': 'finished',
            'completed': 'finished',
            'cancelled': 'cancelled'
        }
        return status_map.get(internal_status.lower() if internal_status else '', 'unknown')
    
    @staticmethod
    def _map_class(patient_class):
        """Map patient class to FHIR encounter class"""
        class_map = {
            'inpatient': {'code': 'IMP', 'display': 'inpatient encounter'},
            'outpatient': {'code': 'AMB', 'display': 'ambulatory'},
            'emergency': {'code': 'EMER', 'display': 'emergency'},
            'observation': {'code': 'OBSENC', 'display': 'observation encounter'}
        }
        return class_map.get(patient_class.lower() if patient_class else '', {'code': 'IMP', 'display': 'inpatient'})


class FHIRObservationConverter:
    """Convert vital signs to FHIR Observation resources"""
    
    VITAL_CODES = {
        'temperature': {'code': '8310-5', 'display': 'Body temperature', 'unit': 'degF'},
        'heart_rate': {'code': '8867-4', 'display': 'Heart rate', 'unit': '/min'},
        'bp_systolic': {'code': '8480-6', 'display': 'Systolic blood pressure', 'unit': 'mmHg'},
        'bp_diastolic': {'code': '8462-4', 'display': 'Diastolic blood pressure', 'unit': 'mmHg'},
        'respiratory_rate': {'code': '9279-1', 'display': 'Respiratory rate', 'unit': '/min'},
        'spo2': {'code': '59408-5', 'display': 'Oxygen saturation in Arterial blood', 'unit': '%'},
        'weight': {'code': '29463-7', 'display': 'Body weight', 'unit': 'kg'},
        'height': {'code': '8302-2', 'display': 'Body height', 'unit': 'cm'},
        'pain_score': {'code': '72514-3', 'display': 'Pain severity - 0-10 verbal numeric rating', 'unit': '{score}'}
    }
    
    @staticmethod
    def to_fhir(vital, vital_type):
        """Convert a single vital sign to FHIR Observation"""
        code_info = FHIRObservationConverter.VITAL_CODES.get(vital_type, {})
        value = getattr(vital, vital_type, None)
        
        if value is None:
            return None
        
        resource = {
            'resourceType': 'Observation',
            'id': f'{vital.id}-{vital_type}',
            'meta': {
                'lastUpdated': vital.recorded_at.isoformat() + 'Z' if vital.recorded_at else datetime.utcnow().isoformat() + 'Z'
            },
            'status': 'final',
            'category': [{
                'coding': [{
                    'system': 'http://terminology.hl7.org/CodeSystem/observation-category',
                    'code': 'vital-signs',
                    'display': 'Vital Signs'
                }]
            }],
            'code': {
                'coding': [{
                    'system': 'http://loinc.org',
                    'code': code_info.get('code', ''),
                    'display': code_info.get('display', vital_type)
                }],
                'text': code_info.get('display', vital_type)
            },
            'subject': {
                'reference': f'Patient/{vital.patient_id}'
            },
            'effectiveDateTime': vital.recorded_at.isoformat() + 'Z' if vital.recorded_at else None,
            'valueQuantity': {
                'value': float(value),
                'unit': code_info.get('unit', ''),
                'system': 'http://unitsofmeasure.org',
                'code': code_info.get('unit', '')
            }
        }
        
        # Add performer
        if hasattr(vital, 'recorded_by') and vital.recorded_by:
            resource['performer'] = [{'display': vital.recorded_by}]
        
        return resource
    
    @staticmethod
    def vitals_to_fhir_bundle(vital):
        """Convert all vital signs from a record to FHIR Observations"""
        observations = []
        
        for vital_type in FHIRObservationConverter.VITAL_CODES.keys():
            obs = FHIRObservationConverter.to_fhir(vital, vital_type)
            if obs:
                observations.append(obs)
        
        # Handle blood pressure as a panel
        if hasattr(vital, 'bp_systolic') and vital.bp_systolic and hasattr(vital, 'bp_diastolic') and vital.bp_diastolic:
            bp_panel = {
                'resourceType': 'Observation',
                'id': f'{vital.id}-bp',
                'status': 'final',
                'category': [{
                    'coding': [{
                        'system': 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code': 'vital-signs'
                    }]
                }],
                'code': {
                    'coding': [{
                        'system': 'http://loinc.org',
                        'code': '85354-9',
                        'display': 'Blood pressure panel'
                    }]
                },
                'subject': {'reference': f'Patient/{vital.patient_id}'},
                'effectiveDateTime': vital.recorded_at.isoformat() + 'Z' if vital.recorded_at else None,
                'component': [
                    {
                        'code': {
                            'coding': [{'system': 'http://loinc.org', 'code': '8480-6', 'display': 'Systolic BP'}]
                        },
                        'valueQuantity': {'value': vital.bp_systolic, 'unit': 'mmHg'}
                    },
                    {
                        'code': {
                            'coding': [{'system': 'http://loinc.org', 'code': '8462-4', 'display': 'Diastolic BP'}]
                        },
                        'valueQuantity': {'value': vital.bp_diastolic, 'unit': 'mmHg'}
                    }
                ]
            }
            observations.append(bp_panel)
        
        return observations


class FHIRMedicationConverter:
    """Convert medications to FHIR resources"""
    
    @staticmethod
    def to_fhir_medication_request(medication):
        """Convert internal medication to FHIR MedicationRequest"""
        resource = {
            'resourceType': 'MedicationRequest',
            'id': str(medication.id),
            'status': FHIRMedicationConverter._map_status(medication.status),
            'intent': 'order',
            'medicationCodeableConcept': {
                'text': medication.name
            },
            'subject': {
                'reference': f'Patient/{medication.patient_id}'
            },
            'authoredOn': medication.ordered_at.isoformat() + 'Z' if hasattr(medication, 'ordered_at') and medication.ordered_at else None
        }
        
        # Add dosage instruction
        if hasattr(medication, 'dose') or hasattr(medication, 'route') or hasattr(medication, 'frequency'):
            dosage = {'text': ''}
            parts = []
            
            if hasattr(medication, 'dose') and medication.dose:
                parts.append(medication.dose)
            if hasattr(medication, 'route') and medication.route:
                parts.append(medication.route)
            if hasattr(medication, 'frequency') and medication.frequency:
                parts.append(medication.frequency)
            
            dosage['text'] = ' '.join(parts)
            
            if hasattr(medication, 'route') and medication.route:
                dosage['route'] = {'text': medication.route}
            
            resource['dosageInstruction'] = [dosage]
        
        # Add prescriber
        if hasattr(medication, 'prescriber') and medication.prescriber:
            resource['requester'] = {'display': medication.prescriber}
        
        return resource
    
    @staticmethod
    def _map_status(internal_status):
        """Map internal status to FHIR status"""
        status_map = {
            'active': 'active',
            'scheduled': 'active',
            'completed': 'completed',
            'discontinued': 'stopped',
            'on_hold': 'on-hold',
            'cancelled': 'cancelled'
        }
        return status_map.get(internal_status.lower() if internal_status else '', 'unknown')


# FHIR API Routes

@fhir_bp.route('/metadata', methods=['GET'])
def capability_statement():
    """Return FHIR CapabilityStatement"""
    return jsonify({
        'resourceType': 'CapabilityStatement',
        'status': 'active',
        'date': datetime.utcnow().isoformat() + 'Z',
        'kind': 'instance',
        'fhirVersion': '4.0.1',
        'format': ['json'],
        'rest': [{
            'mode': 'server',
            'resource': [
                {
                    'type': 'Patient',
                    'interaction': [
                        {'code': 'read'},
                        {'code': 'search-type'},
                        {'code': 'create'},
                        {'code': 'update'}
                    ],
                    'searchParam': [
                        {'name': 'identifier', 'type': 'token'},
                        {'name': 'name', 'type': 'string'},
                        {'name': 'birthdate', 'type': 'date'},
                        {'name': 'gender', 'type': 'token'}
                    ]
                },
                {
                    'type': 'Encounter',
                    'interaction': [
                        {'code': 'read'},
                        {'code': 'search-type'}
                    ],
                    'searchParam': [
                        {'name': 'patient', 'type': 'reference'},
                        {'name': 'status', 'type': 'token'}
                    ]
                },
                {
                    'type': 'Observation',
                    'interaction': [
                        {'code': 'read'},
                        {'code': 'search-type'},
                        {'code': 'create'}
                    ],
                    'searchParam': [
                        {'name': 'patient', 'type': 'reference'},
                        {'name': 'category', 'type': 'token'},
                        {'name': 'code', 'type': 'token'},
                        {'name': 'date', 'type': 'date'}
                    ]
                },
                {
                    'type': 'MedicationRequest',
                    'interaction': [
                        {'code': 'read'},
                        {'code': 'search-type'}
                    ],
                    'searchParam': [
                        {'name': 'patient', 'type': 'reference'},
                        {'name': 'status', 'type': 'token'}
                    ]
                },
                {
                    'type': 'AllergyIntolerance',
                    'interaction': [
                        {'code': 'read'},
                        {'code': 'search-type'}
                    ]
                },
                {
                    'type': 'Condition',
                    'interaction': [
                        {'code': 'read'},
                        {'code': 'search-type'}
                    ]
                },
                {
                    'type': 'DiagnosticReport',
                    'interaction': [
                        {'code': 'read'},
                        {'code': 'search-type'}
                    ]
                }
            ]
        }]
    })


@fhir_bp.route('/Patient', methods=['GET'])
def search_patients():
    """Search for patients"""
    from models.patient import Patient
    
    query = Patient.query
    
    # Search by identifier (MRN)
    identifier = request.args.get('identifier')
    if identifier:
        query = query.filter(Patient.mrn == identifier)
    
    # Search by name
    name = request.args.get('name')
    if name:
        query = query.filter(
            (Patient.first_name.ilike(f'%{name}%')) |
            (Patient.last_name.ilike(f'%{name}%'))
        )
    
    # Search by family name
    family = request.args.get('family')
    if family:
        query = query.filter(Patient.last_name.ilike(f'%{family}%'))
    
    # Search by given name
    given = request.args.get('given')
    if given:
        query = query.filter(Patient.first_name.ilike(f'%{given}%'))
    
    # Search by birthdate
    birthdate = request.args.get('birthdate')
    if birthdate:
        try:
            dob = datetime.strptime(birthdate, '%Y-%m-%d').date()
            query = query.filter(Patient.date_of_birth == dob)
        except ValueError:
            pass
    
    # Search by gender
    gender = request.args.get('gender')
    if gender:
        query = query.filter(Patient.gender.ilike(gender))
    
    # Pagination
    count = request.args.get('_count', 20, type=int)
    offset = request.args.get('_offset', 0, type=int)
    
    total = query.count()
    patients = query.limit(count).offset(offset).all()
    
    resources = [FHIRPatientConverter.to_fhir(p) for p in patients]
    
    return jsonify(create_fhir_bundle(resources, total=total))


@fhir_bp.route('/Patient/<int:patient_id>', methods=['GET'])
def get_patient(patient_id):
    """Get a single patient by ID"""
    from models.patient import Patient
    
    patient = Patient.query.get(patient_id)
    if not patient:
        return jsonify(create_operation_outcome('error', 'not-found', f'Patient {patient_id} not found')), 404
    
    return jsonify(FHIRPatientConverter.to_fhir(patient))


@fhir_bp.route('/Patient', methods=['POST'])
def create_patient():
    """Create a new patient from FHIR resource"""
    from models.patient import Patient, db
    
    fhir_resource = request.get_json()
    
    if fhir_resource.get('resourceType') != 'Patient':
        return jsonify(create_operation_outcome('error', 'invalid', 'Expected Patient resource')), 400
    
    try:
        data = FHIRPatientConverter.from_fhir(fhir_resource)
        patient = Patient(**data)
        db.session.add(patient)
        db.session.commit()
        
        response = jsonify(FHIRPatientConverter.to_fhir(patient))
        response.status_code = 201
        response.headers['Location'] = f'/api/fhir/r4/Patient/{patient.id}'
        return response
        
    except Exception as e:
        return jsonify(create_operation_outcome('error', 'exception', str(e))), 400


@fhir_bp.route('/Encounter', methods=['GET'])
def search_encounters():
    """Search for encounters"""
    from models.encounter import Encounter
    
    query = Encounter.query
    
    # Search by patient
    patient = request.args.get('patient')
    if patient:
        patient_id = patient.replace('Patient/', '')
        query = query.filter(Encounter.patient_id == int(patient_id))
    
    # Search by status
    status = request.args.get('status')
    if status:
        query = query.filter(Encounter.status == status)
    
    encounters = query.all()
    resources = [FHIREncounterConverter.to_fhir(e) for e in encounters]
    
    return jsonify(create_fhir_bundle(resources))


@fhir_bp.route('/Encounter/<int:encounter_id>', methods=['GET'])
def get_encounter(encounter_id):
    """Get a single encounter"""
    from models.encounter import Encounter
    
    encounter = Encounter.query.get(encounter_id)
    if not encounter:
        return jsonify(create_operation_outcome('error', 'not-found', f'Encounter {encounter_id} not found')), 404
    
    return jsonify(FHIREncounterConverter.to_fhir(encounter))


@fhir_bp.route('/Observation', methods=['GET'])
def search_observations():
    """Search for observations (vital signs, labs)"""
    from models.vital import Vital
    
    patient = request.args.get('patient')
    category = request.args.get('category')
    
    if not patient:
        return jsonify(create_operation_outcome('error', 'required', 'patient parameter is required')), 400
    
    patient_id = patient.replace('Patient/', '')
    
    # Get vitals for patient
    vitals = Vital.query.filter_by(patient_id=int(patient_id)).order_by(Vital.recorded_at.desc()).limit(100).all()
    
    resources = []
    for vital in vitals:
        resources.extend(FHIRObservationConverter.vitals_to_fhir_bundle(vital))
    
    return jsonify(create_fhir_bundle(resources))


@fhir_bp.route('/MedicationRequest', methods=['GET'])
def search_medication_requests():
    """Search for medication requests"""
    from models.medication import Medication
    
    patient = request.args.get('patient')
    status = request.args.get('status')
    
    query = Medication.query
    
    if patient:
        patient_id = patient.replace('Patient/', '')
        query = query.filter(Medication.patient_id == int(patient_id))
    
    if status:
        query = query.filter(Medication.status == status)
    
    medications = query.all()
    resources = [FHIRMedicationConverter.to_fhir_medication_request(m) for m in medications]
    
    return jsonify(create_fhir_bundle(resources))


@fhir_bp.route('/AllergyIntolerance', methods=['GET'])
def search_allergies():
    """Search for allergies"""
    from models.patient import Allergy
    
    patient = request.args.get('patient')
    
    if not patient:
        return jsonify(create_operation_outcome('error', 'required', 'patient parameter is required')), 400
    
    patient_id = patient.replace('Patient/', '')
    allergies = Allergy.query.filter_by(patient_id=int(patient_id)).all()
    
    resources = []
    for allergy in allergies:
        resource = {
            'resourceType': 'AllergyIntolerance',
            'id': str(allergy.id),
            'clinicalStatus': {
                'coding': [{
                    'system': 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                    'code': 'active'
                }]
            },
            'verificationStatus': {
                'coding': [{
                    'system': 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification',
                    'code': 'confirmed'
                }]
            },
            'type': 'allergy',
            'category': [allergy.category.lower() if hasattr(allergy, 'category') and allergy.category else 'medication'],
            'criticality': 'high' if allergy.severity == 'Severe' else 'low',
            'code': {
                'text': allergy.allergen
            },
            'patient': {
                'reference': f'Patient/{allergy.patient_id}'
            },
            'reaction': [{
                'manifestation': [{'text': allergy.reaction}]
            }] if allergy.reaction else []
        }
        resources.append(resource)
    
    return jsonify(create_fhir_bundle(resources))


@fhir_bp.route('/$everything', methods=['GET'])
def patient_everything():
    """Get all resources for a patient (Patient/$everything)"""
    patient_id = request.args.get('patient')
    if not patient_id:
        return jsonify(create_operation_outcome('error', 'required', 'patient parameter is required')), 400
    
    patient_id = patient_id.replace('Patient/', '')
    
    # Collect all resources
    resources = []
    
    # Patient
    from models.patient import Patient, Allergy
    patient = Patient.query.get(int(patient_id))
    if patient:
        resources.append(FHIRPatientConverter.to_fhir(patient))
    
    # Encounters
    from models.encounter import Encounter
    encounters = Encounter.query.filter_by(patient_id=int(patient_id)).all()
    for enc in encounters:
        resources.append(FHIREncounterConverter.to_fhir(enc))
    
    # Vitals/Observations
    from models.vital import Vital
    vitals = Vital.query.filter_by(patient_id=int(patient_id)).all()
    for vital in vitals:
        resources.extend(FHIRObservationConverter.vitals_to_fhir_bundle(vital))
    
    # Medications
    from models.medication import Medication
    meds = Medication.query.filter_by(patient_id=int(patient_id)).all()
    for med in meds:
        resources.append(FHIRMedicationConverter.to_fhir_medication_request(med))
    
    # Allergies
    allergies = Allergy.query.filter_by(patient_id=int(patient_id)).all()
    for allergy in allergies:
        resource = {
            'resourceType': 'AllergyIntolerance',
            'id': str(allergy.id),
            'code': {'text': allergy.allergen},
            'patient': {'reference': f'Patient/{patient_id}'}
        }
        resources.append(resource)
    
    return jsonify(create_fhir_bundle(resources, bundle_type='collection'))
