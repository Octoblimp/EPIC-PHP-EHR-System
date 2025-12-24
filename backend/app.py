"""
Epic EHR Backend Server - Main Application
Flask-based API server for Epic EHR System clone
"""
from flask import Flask, jsonify
from flask_cors import CORS
from config import config
from models import db
from models.patient import Patient, Allergy
from models.encounter import Encounter
from models.medication import Medication, MedicationAdministration
from models.order import Order, OrderSet
from models.vital import Vital
from models.flowsheet import FlowsheetTemplate, FlowsheetEntry, FlowsheetSection
from models.lab import LabResult
from models.note import Note, SmartPhrase
from models.user import User

def create_app(config_name='default'):
    """Application factory"""
    app = Flask(__name__)
    app.config.from_object(config[config_name])
    
    # Initialize extensions
    db.init_app(app)
    CORS(app, resources={r"/api/*": {"origins": "*"}})
    
    # Register blueprints
    from routes.patient_routes import patient_bp
    from routes.medication_routes import medication_bp
    from routes.order_routes import order_bp
    from routes.vital_routes import vital_bp
    from routes.flowsheet_routes import flowsheet_bp
    from routes.lab_routes import lab_bp
    from routes.note_routes import note_bp
    
    app.register_blueprint(patient_bp, url_prefix='/api/patients')
    app.register_blueprint(medication_bp, url_prefix='/api/medications')
    app.register_blueprint(order_bp, url_prefix='/api/orders')
    app.register_blueprint(vital_bp, url_prefix='/api/vitals')
    app.register_blueprint(flowsheet_bp, url_prefix='/api/flowsheets')
    app.register_blueprint(lab_bp, url_prefix='/api/labs')
    app.register_blueprint(note_bp, url_prefix='/api/notes')
    
    # Root endpoint
    @app.route('/')
    def index():
        return jsonify({
            'name': 'Epic EHR Backend API',
            'version': '1.0.0',
            'status': 'running',
            'endpoints': {
                'patients': '/api/patients',
                'medications': '/api/medications',
                'orders': '/api/orders',
                'vitals': '/api/vitals',
                'flowsheets': '/api/flowsheets',
                'labs': '/api/labs',
                'notes': '/api/notes'
            }
        })
    
    # Health check
    @app.route('/health')
    def health():
        return jsonify({'status': 'healthy'})
    
    # Error handlers
    @app.errorhandler(404)
    def not_found(e):
        return jsonify({'success': False, 'error': 'Resource not found'}), 404
    
    @app.errorhandler(500)
    def server_error(e):
        return jsonify({'success': False, 'error': 'Internal server error'}), 500
    
    return app

# Create tables and seed data
def init_db(app):
    """Initialize database with tables and sample data"""
    with app.app_context():
        db.create_all()
        
        # Check if data already exists
        if Patient.query.first() is None:
            seed_database()
            print("Database seeded with sample data!")
        else:
            print("Database already contains data.")

def seed_database():
    """Seed the database with sample EHR data"""
    from datetime import datetime, timedelta
    import random
    
    # Create sample users
    users = [
        User(
            username='drsmith',
            first_name='John',
            last_name='Smith',
            email='john.smith@hospital.org',
            role='Physician',
            department='Internal Medicine',
            title='MD',
            is_provider=True,
            can_order=True,
            can_prescribe=True
        ),
        User(
            username='nursejones',
            first_name='Sarah',
            last_name='Jones',
            email='sarah.jones@hospital.org',
            role='Nurse',
            department='Medical/Surgical',
            title='RN',
            is_provider=False,
            can_order=False,
            can_prescribe=False
        ),
        User(
            username='drsandhu',
            first_name='Ritu Raj',
            last_name='Sandhu',
            email='ritu.sandhu@hospital.org',
            role='Physician',
            department='OB/GYN',
            title='MD',
            is_provider=True,
            can_order=True,
            can_prescribe=True
        )
    ]
    
    for user in users:
        user.set_password('password123')
        db.session.add(user)
    
    # Create sample patients
    patients = [
        Patient(
            mrn='E1404907',
            csn='15309',
            first_name='Melissa',
            last_name='Testmonday',
            middle_name='A',
            date_of_birth=datetime(1974, 2, 12),
            gender='Female',
            address='123 Main Street',
            city='Springfield',
            state='MO',
            zip_code='65801',
            phone_home='555-123-4567',
            blood_type='O+',
            primary_care_provider='Dr. John Smith, MD',
            insurance_plan='Blue Cross Blue Shield'
        ),
        Patient(
            mrn='4802001',
            csn='400029',
            first_name='Mary',
            last_name='Smith',
            date_of_birth=datetime(1990, 11, 11),
            gender='Female',
            address='456 Oak Avenue',
            city='Raleigh',
            state='NC',
            zip_code='27601',
            phone_home='555-987-6543',
            blood_type='A+',
            primary_care_provider='Dr. Ritu Raj Sandhu, MD',
            insurance_plan='Aetna'
        ),
        Patient(
            mrn='E1505123',
            csn='16001',
            first_name='Robert',
            last_name='Johnson',
            middle_name='D',
            date_of_birth=datetime(1965, 5, 20),
            gender='Male',
            address='789 Pine Road',
            city='Springfield',
            state='MO',
            zip_code='65802',
            phone_home='555-456-7890',
            blood_type='B-',
            primary_care_provider='Dr. John Smith, MD',
            insurance_plan='Medicare'
        )
    ]
    
    for patient in patients:
        db.session.add(patient)
    
    db.session.flush()  # Get patient IDs
    
    # Create allergies
    allergies = [
        Allergy(patient_id=patients[1].id, allergen='Penicillin', reaction='Rash', severity='Moderate', allergy_type='Drug'),
        Allergy(patient_id=patients[2].id, allergen='Sulfa', reaction='Anaphylaxis', severity='Severe', allergy_type='Drug'),
        Allergy(patient_id=patients[2].id, allergen='Shellfish', reaction='Hives', severity='Moderate', allergy_type='Food'),
    ]
    
    for allergy in allergies:
        db.session.add(allergy)
    
    # Create encounters
    encounters = [
        Encounter(
            patient_id=patients[0].id,
            csn='15309',
            encounter_type='Inpatient',
            patient_class='Inpatient',
            admission_date=datetime(2018, 2, 12),
            facility='Mercy Hospital Springfield',
            department='Pastoral Care',
            room='302',
            bed='A',
            unit='3 North',
            attending_provider='Dr. John Smith, MD - Attending',
            admitting_provider='Dr. John Smith, MD',
            level_of_care='None',
            code_status='Full Code',
            status='Active',
            ad_lwi_received=datetime(2018, 2, 12),
            ad_poa_declined=datetime(2018, 2, 12)
        ),
        Encounter(
            patient_id=patients[1].id,
            csn='400029',
            encounter_type='Inpatient',
            patient_class='Outpatient in a Bed',
            admission_date=datetime(2017, 9, 11),
            facility='WRC 4B L&D',
            department='Labor & Delivery',
            room='4B',
            bed='1',
            unit='L&D',
            attending_provider='Dr. Ritu Raj Sandhu, MD',
            treatment_team='Dr. Ritu Raj Sandhu, MD',
            level_of_care='None',
            code_status='Full Code',
            status='Active',
            chief_complaint='Post Partum Hemorrhage'
        ),
        Encounter(
            patient_id=patients[2].id,
            csn='16001',
            encounter_type='Inpatient',
            patient_class='Inpatient',
            admission_date=datetime.now() - timedelta(days=2),
            facility='Mercy Hospital Springfield',
            department='Internal Medicine',
            room='415',
            bed='B',
            unit='4 South',
            attending_provider='Dr. John Smith, MD',
            level_of_care='Acute',
            code_status='Full Code',
            status='Active',
            chief_complaint='Chest pain, shortness of breath'
        )
    ]
    
    for encounter in encounters:
        db.session.add(encounter)
    
    db.session.flush()
    
    # Create medications for patient 2 (Post Partum Hemorrhage case)
    medications = [
        Medication(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            name='oxytocin 0.9 % sod chloride (PITOCIN)',
            generic_name='Oxytocin',
            dose='30',
            dose_unit='unit/500 mL',
            route='IV',
            frequency='Continuous',
            status='Active',
            med_type='Continuous',
            is_high_alert=True,
            ordering_provider='Dr. Ritu Raj Sandhu, MD'
        ),
        Medication(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            name='methylergonovine (METHERGINE)',
            generic_name='Methylergonovine',
            dose='0.2',
            dose_unit='mg/mL',
            route='IM',
            frequency='x1',
            status='Active',
            med_type='One-time',
            ordering_provider='Dr. Ritu Raj Sandhu, MD'
        ),
        Medication(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            name='misoPROStol (CYTOTEC)',
            generic_name='Misoprostol',
            dose='200',
            dose_unit='MCG',
            route='PR',
            frequency='x1',
            status='Active',
            med_type='One-time',
            ordering_provider='Dr. Ritu Raj Sandhu, MD'
        ),
        Medication(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            name='carboprost (HEMABATE)',
            generic_name='Carboprost',
            dose='250',
            dose_unit='mcg/mL',
            route='IM',
            frequency='PRN',
            status='Active',
            med_type='PRN',
            ordering_provider='Dr. Ritu Raj Sandhu, MD'
        ),
        Medication(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            name='diphenhydrAMINE-atropine (LOMOTIL)',
            dose='2.5-0.025',
            dose_unit='mg',
            route='PO',
            frequency='PRN',
            status='Active',
            med_type='PRN',
            ordering_provider='Dr. Ritu Raj Sandhu, MD'
        ),
        Medication(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            name='acetaminophen (TYLENOL)',
            generic_name='Acetaminophen',
            dose='325',
            dose_unit='MG',
            route='PO',
            frequency='Q4H PRN',
            status='Active',
            med_type='PRN',
            ordering_provider='Dr. Ritu Raj Sandhu, MD'
        ),
        Medication(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            name='cefazolin (ANCEF)',
            generic_name='Cefazolin',
            dose='1',
            dose_unit='gram',
            route='IV',
            frequency='Q8H',
            status='Active',
            med_type='Scheduled',
            ordering_provider='Dr. Ritu Raj Sandhu, MD'
        )
    ]
    
    for med in medications:
        db.session.add(med)
    
    # Create vitals for patient 2
    vitals = [
        Vital(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            recorded_date=datetime(2017, 2, 9, 15, 57, 46),
            recorded_by='Nurse Jones, RN',
            temperature=98.6,
            temp_source='Oral',
            heart_rate=88,
            respiratory_rate=18,
            bp_systolic=118,
            bp_diastolic=72,
            spo2=98,
            o2_device='Room Air',
            pain_score=3
        )
    ]
    
    for vital in vitals:
        db.session.add(vital)
    
    # Create orders for patient 2
    orders = [
        Order(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            order_name='Sequential Compression Device',
            order_type='Nursing',
            priority='Routine',
            ordering_provider='Dr. Ritu Raj Sandhu, MD',
            status='Ordered',
            acknowledgement_status='Pending'
        ),
        Order(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            order_name='CBC with Differential',
            order_type='Lab',
            priority='Stat',
            ordering_provider='Dr. Ritu Raj Sandhu, MD',
            status='Completed',
            result_status='Final'
        ),
        Order(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            order_name='Type and Screen',
            order_type='Lab',
            priority='Stat',
            ordering_provider='Dr. Ritu Raj Sandhu, MD',
            status='Completed',
            result_status='Final'
        )
    ]
    
    for order in orders:
        db.session.add(order)
    
    # Create flowsheet entries for patient 1 (Pastoral Services)
    flowsheet_entries = [
        FlowsheetEntry(
            patient_id=patients[0].id,
            encounter_id=encounters[0].id,
            row_name='Reason for Visit',
            value='Follow-up',
            section='Reason For Visit',
            flowsheet_group='Pastoral Services',
            entry_datetime=datetime(2018, 5, 20, 15, 0),
            documented_by='Pastoral Services'
        ),
        FlowsheetEntry(
            patient_id=patients[0].id,
            encounter_id=encounters[0].id,
            row_name='Pastoral Care Notified Faith Community?',
            value='Yes',
            section='Religious Affiliation',
            flowsheet_group='Pastoral Services',
            entry_datetime=datetime(2018, 5, 20, 15, 0),
            documented_by='Pastoral Services'
        ),
        FlowsheetEntry(
            patient_id=patients[0].id,
            encounter_id=encounters[0].id,
            row_name='Who Was Present for the Visit',
            value='Patient/Parent / Legal Guardian',
            section='Visit Information',
            flowsheet_group='Pastoral Services',
            entry_datetime=datetime(2018, 5, 20, 15, 0),
            documented_by='Pastoral Services'
        ),
        FlowsheetEntry(
            patient_id=patients[0].id,
            encounter_id=encounters[0].id,
            row_name='Pastoral Services Follow-up',
            value='Contact with family but not with patient',
            section='Visit Information',
            flowsheet_group='Pastoral Services',
            entry_datetime=datetime(2018, 5, 20, 15, 0),
            documented_by='Pastoral Services'
        ),
        FlowsheetEntry(
            patient_id=patients[0].id,
            encounter_id=encounters[0].id,
            row_name='Communion Asked',
            value='No',
            section='Sacraments',
            flowsheet_group='Pastoral Services',
            entry_datetime=datetime(2018, 5, 20, 15, 0),
            documented_by='Pastoral Services'
        )
    ]
    
    for entry in flowsheet_entries:
        db.session.add(entry)
    
    # Create flowsheet entries for patient 2 (Post Partum Hemorrhage)
    pph_entries = [
        FlowsheetEntry(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            row_name='Code Start',
            value='15:57:46',
            section='Events',
            flowsheet_group='Post Partum Hemorrhage',
            entry_datetime=datetime(2017, 2, 9, 15, 57, 46),
            documented_by='Nurse Jones, RN',
            comments='Code initiated'
        ),
        FlowsheetEntry(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            row_name='Level of consciousness',
            value='Alert',
            section='Documentation',
            flowsheet_group='Post Partum Hemorrhage',
            entry_datetime=datetime(2017, 2, 9, 16, 0),
            documented_by='Nurse Jones, RN'
        ),
        FlowsheetEntry(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            row_name='Fundus',
            value='Firm',
            section='Bleeding Assessment Summary',
            flowsheet_group='Post Partum Hemorrhage',
            entry_datetime=datetime(2017, 2, 9, 16, 5),
            documented_by='Nurse Jones, RN'
        ),
        FlowsheetEntry(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            row_name='Lochia or Bleeding',
            value='Heavy',
            section='Bleeding Assessment Summary',
            flowsheet_group='Post Partum Hemorrhage',
            entry_datetime=datetime(2017, 2, 9, 16, 5),
            documented_by='Nurse Jones, RN'
        ),
        FlowsheetEntry(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            row_name='Blood Collection without LDA',
            value='Collected',
            section='Interventions',
            flowsheet_group='Post Partum Hemorrhage',
            entry_datetime=datetime(2017, 2, 9, 16, 10),
            documented_by='Nurse Jones, RN'
        )
    ]
    
    for entry in pph_entries:
        db.session.add(entry)
    
    # Create lab results for patient 2
    lab_results = [
        LabResult(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            test_name='Hemoglobin',
            test_code='HGB',
            panel_name='CBC',
            value='9.2',
            numeric_value=9.2,
            unit='g/dL',
            reference_low=12.0,
            reference_high=16.0,
            flag='Low',
            resulted_date=datetime(2017, 2, 9, 16, 30),
            status='Final'
        ),
        LabResult(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            test_name='Hematocrit',
            test_code='HCT',
            panel_name='CBC',
            value='27.5',
            numeric_value=27.5,
            unit='%',
            reference_low=36.0,
            reference_high=46.0,
            flag='Low',
            resulted_date=datetime(2017, 2, 9, 16, 30),
            status='Final'
        ),
        LabResult(
            patient_id=patients[1].id,
            encounter_id=encounters[1].id,
            test_name='Platelet Count',
            test_code='PLT',
            panel_name='CBC',
            value='185',
            numeric_value=185,
            unit='K/uL',
            reference_low=150,
            reference_high=400,
            resulted_date=datetime(2017, 2, 9, 16, 30),
            status='Final'
        )
    ]
    
    for lab in lab_results:
        db.session.add(lab)
    
    db.session.commit()
    print("Sample data created successfully!")


if __name__ == '__main__':
    app = create_app('development')
    init_db(app)
    app.run(host='0.0.0.0', port=5000, debug=True)
