"""
Insurance Routes - Eligibility verification and insurance management
"""
from flask import Blueprint, request, jsonify
from datetime import datetime, date
import json

insurance_bp = Blueprint('insurance', __name__)


# Demo clearinghouse configs
DEMO_CLEARINGHOUSES = {
    'availity': {
        'id': 1,
        'name': 'Availity',
        'provider': 'availity',
        'is_active': True,
        'is_primary': True,
        'test_mode': True
    }
}

# Demo insurance payers with EDI IDs
DEMO_PAYERS = {
    'BCBS': {'name': 'Blue Cross Blue Shield', 'edi_id': '00060', 'phone': '1-800-262-2583'},
    'AETNA': {'name': 'Aetna', 'edi_id': '60054', 'phone': '1-800-872-3862'},
    'CIGNA': {'name': 'Cigna', 'edi_id': '62308', 'phone': '1-800-997-1654'},
    'UHC': {'name': 'UnitedHealthcare', 'edi_id': '87726', 'phone': '1-800-328-5979'},
    'MEDICARE': {'name': 'Medicare', 'edi_id': '00882', 'phone': '1-800-633-4227'},
    'MEDICAID': {'name': 'Medicaid', 'edi_id': 'VARID', 'phone': 'Varies by state'},
    'HUMANA': {'name': 'Humana', 'edi_id': '61101', 'phone': '1-800-448-6262'},
}


@insurance_bp.route('/api/insurance/clearinghouses', methods=['GET'])
def get_clearinghouses():
    """Get configured clearinghouses"""
    return jsonify({
        'success': True,
        'clearinghouses': list(DEMO_CLEARINGHOUSES.values()),
        'primary': next((c for c in DEMO_CLEARINGHOUSES.values() if c.get('is_primary')), None)
    })


@insurance_bp.route('/api/insurance/payers', methods=['GET'])
def get_payers():
    """Get list of insurance payers with EDI IDs"""
    payers = [{'id': k, **v} for k, v in DEMO_PAYERS.items()]
    return jsonify({
        'success': True,
        'payers': payers
    })


@insurance_bp.route('/api/insurance/eligibility/check', methods=['POST'])
def check_eligibility():
    """
    Check insurance eligibility via clearinghouse (270/271)
    In production, this would make actual EDI or API calls
    """
    data = request.get_json()
    
    patient_id = data.get('patient_id')
    coverage_id = data.get('coverage_id')
    coverage_level = data.get('coverage_level', 'primary')
    payer_id = data.get('payer_id')
    subscriber_id = data.get('subscriber_id')
    member_dob = data.get('member_dob')
    service_date = data.get('service_date', datetime.now().strftime('%Y-%m-%d'))
    
    # In production: Make actual EDI 270 request
    # For demo: Return simulated 271 response
    
    # Simulate response time
    import time
    import random
    time.sleep(0.5)  # Simulate network delay
    
    # Generate demo response based on payer
    demo_responses = {
        'eligible': {
            'is_eligible': True,
            'coverage_status': 'Active',
            'response_code': 'AAA',
            'response_message': 'Coverage confirmed active'
        },
        'inactive': {
            'is_eligible': False,
            'coverage_status': 'Inactive',
            'response_code': '72',
            'response_message': 'Coverage terminated'
        },
        'not_found': {
            'is_eligible': None,
            'coverage_status': 'Unknown',
            'response_code': '75',
            'response_message': 'Subscriber/member not found'
        }
    }
    
    # Randomly select response for demo (90% eligible)
    outcome = 'eligible' if random.random() < 0.9 else random.choice(['inactive', 'not_found'])
    response_data = demo_responses[outcome]
    
    # Build benefits info
    benefits = {
        'deductible': {
            'individual': {'amount': 500.00, 'remaining': 150.00},
            'family': {'amount': 1500.00, 'remaining': 450.00}
        },
        'out_of_pocket': {
            'individual': {'amount': 3000.00, 'remaining': 2150.00},
            'family': {'amount': 9000.00, 'remaining': 6450.00}
        },
        'copays': {
            'primary_care': 25.00,
            'specialist': 50.00,
            'urgent_care': 75.00,
            'emergency': 250.00
        },
        'coinsurance': 80
    }
    
    # Create transaction record
    transaction = {
        'id': random.randint(10000, 99999),
        'patient_id': patient_id,
        'coverage_id': coverage_id,
        'coverage_level': coverage_level,
        'transaction_type': '270_request',
        'service_date': service_date,
        'payer_name': DEMO_PAYERS.get(payer_id, {}).get('name', 'Unknown Payer'),
        'subscriber_id': subscriber_id,
        'is_eligible': response_data['is_eligible'],
        'coverage_status': response_data['coverage_status'],
        'response_status': 'success',
        'response_code': response_data['response_code'],
        'response_message': response_data['response_message'],
        'benefits': benefits if response_data['is_eligible'] else None,
        'transaction_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'trace_number': f"TRN{random.randint(100000000, 999999999)}",
        'clearinghouse': 'Availity (Demo)',
        'response_time_ms': random.randint(200, 800)
    }
    
    return jsonify({
        'success': True,
        'transaction': transaction,
        'message': response_data['response_message']
    })


@insurance_bp.route('/api/insurance/eligibility/manual', methods=['POST'])
def manual_eligibility():
    """
    Record manual eligibility verification
    For when user verifies via phone or payer portal
    """
    data = request.get_json()
    
    patient_id = data.get('patient_id')
    coverage_id = data.get('coverage_id')
    coverage_level = data.get('coverage_level', 'primary')
    payer_name = data.get('payer_name')
    is_eligible = data.get('is_eligible', True)
    coverage_status = data.get('coverage_status', 'Active')
    verified_by = data.get('verified_by')
    verification_method = data.get('verification_method')  # phone, portal
    reference_number = data.get('reference_number')
    contact_name = data.get('contact_name')
    notes = data.get('notes')
    
    # Benefits from manual entry
    benefits = {
        'deductible': {
            'individual': {
                'amount': float(data.get('deductible_individual', 0)),
                'remaining': float(data.get('deductible_remaining', 0))
            }
        },
        'out_of_pocket': {
            'individual': {
                'amount': float(data.get('oop_max', 0)),
                'remaining': float(data.get('oop_remaining', 0))
            }
        },
        'copays': {
            'primary_care': float(data.get('copay_pcp', 0)),
            'specialist': float(data.get('copay_specialist', 0)),
            'emergency': float(data.get('copay_er', 0))
        }
    }
    
    import random
    
    # Create transaction record
    transaction = {
        'id': random.randint(10000, 99999),
        'patient_id': patient_id,
        'coverage_id': coverage_id,
        'coverage_level': coverage_level,
        'transaction_type': 'manual',
        'service_date': datetime.now().strftime('%Y-%m-%d'),
        'payer_name': payer_name,
        'is_eligible': is_eligible,
        'coverage_status': coverage_status,
        'response_status': 'success',
        'response_message': f"Manually verified via {verification_method}",
        'benefits': benefits,
        'transaction_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'manual_verified_by': verified_by,
        'manual_reference': reference_number,
        'manual_notes': notes
    }
    
    return jsonify({
        'success': True,
        'transaction': transaction,
        'message': 'Manual verification recorded successfully'
    })


@insurance_bp.route('/api/insurance/eligibility/history/<int:patient_id>', methods=['GET'])
def eligibility_history(patient_id):
    """Get eligibility check history for a patient"""
    coverage_level = request.args.get('coverage_level', 'all')
    
    # Demo history data
    history = [
        {
            'id': 1,
            'coverage_level': 'primary',
            'transaction_type': '270_request',
            'payer_name': 'Blue Cross Blue Shield',
            'is_eligible': True,
            'coverage_status': 'Active',
            'transaction_date': (datetime.now()).strftime('%Y-%m-%d %H:%M'),
            'created_by': 'Registration Staff',
            'clearinghouse': 'Availity'
        },
        {
            'id': 2,
            'coverage_level': 'primary',
            'transaction_type': 'manual',
            'payer_name': 'Blue Cross Blue Shield',
            'is_eligible': True,
            'coverage_status': 'Active',
            'transaction_date': (datetime.now()).strftime('%Y-%m-%d %H:%M'),
            'created_by': 'Front Desk',
            'manual_verified_by': 'Jane Smith',
            'manual_notes': 'Verified via BCBS portal'
        }
    ]
    
    if coverage_level != 'all':
        history = [h for h in history if h['coverage_level'] == coverage_level]
    
    return jsonify({
        'success': True,
        'history': history
    })


@insurance_bp.route('/api/insurance/coverage/<int:patient_id>', methods=['GET'])
def get_patient_coverage(patient_id):
    """Get all insurance coverage for a patient"""
    # Demo data - in production from database
    coverage = {
        'primary': {
            'id': 1,
            'payer_id': 'BCBS',
            'payer_name': 'Blue Cross Blue Shield',
            'payer_phone': '1-800-262-2583',
            'plan_name': 'PPO Gold',
            'plan_type': 'PPO',
            'policy_number': 'BCB123456789',
            'group_number': 'GRP001',
            'group_name': 'ABC Corporation',
            'subscriber_id': 'BCB123456789',
            'subscriber_name': 'John Smith',
            'subscriber_relationship': 'Self',
            'subscriber_dob': '03/15/1955',
            'effective_date': '01/01/2024',
            'termination_date': None,
            'copay_primary': 25,
            'copay_specialist': 50,
            'copay_emergency': 250,
            'deductible': 500,
            'deductible_met': 350,
            'out_of_pocket_max': 3000,
            'out_of_pocket_met': 850,
            'requires_referral': False,
            'requires_preauth': True,
            'is_verified': True,
            'verification_date': datetime.now().strftime('%m/%d/%Y'),
            'is_active': True
        },
        'secondary': {
            'id': 2,
            'payer_id': 'MEDICARE',
            'payer_name': 'Medicare',
            'payer_phone': '1-800-633-4227',
            'plan_name': 'Part B',
            'policy_number': '1EG4-TE5-MK72',
            'effective_date': '03/01/2020',
            'is_verified': True,
            'verification_date': datetime.now().strftime('%m/%d/%Y'),
            'is_active': True
        },
        'tertiary': None
    }
    
    return jsonify({
        'success': True,
        'coverage': coverage
    })


@insurance_bp.route('/api/insurance/coverage/<int:patient_id>/<level>', methods=['PUT'])
def update_coverage(patient_id, level):
    """Update insurance coverage"""
    data = request.get_json()
    
    # In production: Update database
    # For demo: Just return success
    
    return jsonify({
        'success': True,
        'message': f'{level.capitalize()} insurance updated successfully',
        'coverage': data
    })


@insurance_bp.route('/api/insurance/coverage/<int:patient_id>/<level>', methods=['DELETE'])
def delete_coverage(patient_id, level):
    """Delete insurance coverage"""
    # In production: Delete from database
    
    return jsonify({
        'success': True,
        'message': f'{level.capitalize()} insurance removed'
    })
