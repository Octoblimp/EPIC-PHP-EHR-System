"""
Database Encryption Migration Script
Encrypts all existing PHI/PII data in the database using AES-256-GCM.

IMPORTANT: Run this script ONLY after backing up your database!
This is a one-way migration - unencrypted data will be replaced with encrypted data.

Usage:
    python -m utils.encrypt_database --analyze    # Analyze unencrypted data
    python -m utils.encrypt_database --encrypt    # Encrypt all data
    python -m utils.encrypt_database --verify     # Verify encryption
"""

import os
import sys
import argparse
from datetime import datetime
from typing import Dict, List, Any

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from flask import Flask
from models import db
from utils.encryption import (
    get_encryption, 
    encrypt_field, 
    decrypt_field,
    hash_for_search
)


# Define tables and fields to encrypt
ENCRYPTION_SCHEMA = {
    'patients': {
        'fields': [
            'first_name',
            'last_name', 
            'middle_name',
            'date_of_birth',  # Will be encrypted as string
            'gender',
            'ssn_last_four',
            'address',
            'city',
            'state',
            'zip_code',
            'phone_home',
            'phone_cell',
            'email',
            'blood_type',
            'height_inches',
            'weight_lbs',
            'bmi',
            'primary_care_provider',
            'insurance_plan',
            'preferred_language',
        ],
        'searchable_fields': ['mrn', 'last_name'],  # These get search hashes
        'exclude': ['id', 'created_at', 'updated_at', 'is_active']
    },
    'users': {
        'fields': [
            'first_name',
            'last_name',
            'email',
            'npi',
            'department',
            'specialty',
            'title',
        ],
        'searchable_fields': ['username'],
        'exclude': ['id', 'password_hash', 'created_at', 'updated_at', 'is_active']
    },
    'allergies': {
        'fields': [
            'allergen',
            'reaction',
            'severity',
            'allergy_type',
            'verified_by',
        ],
        'exclude': ['id', 'patient_id', 'onset_date', 'verified_date', 'is_active']
    },
    'encounters': {
        'fields': [
            'chief_complaint',
            'room_number',
            'bed',
            'attending_provider',
            'primary_nurse',
            'notes',
        ],
        'exclude': ['id', 'patient_id', 'created_at', 'updated_at']
    },
    'clinical_notes': {
        'fields': [
            'title',
            'content',
            'addendum',
            'author_name',
        ],
        'exclude': ['id', 'patient_id', 'encounter_id', 'created_at', 'updated_at']
    },
    'vitals': {
        'fields': [
            'temperature',
            'pulse',
            'respirations',
            'blood_pressure_systolic',
            'blood_pressure_diastolic',
            'oxygen_saturation',
            'pain_level',
            'weight',
            'height',
            'recorded_by',
            'notes',
        ],
        'exclude': ['id', 'patient_id', 'encounter_id', 'recorded_at']
    },
    'medications': {
        'fields': [
            'medication_name',
            'dose',
            'frequency',
            'route',
            'prescriber',
            'pharmacy',
            'instructions',
        ],
        'exclude': ['id', 'patient_id', 'created_at', 'updated_at', 'is_active']
    },
    'orders': {
        'fields': [
            'order_type',
            'description',
            'details',
            'ordering_provider',
            'notes',
        ],
        'exclude': ['id', 'patient_id', 'encounter_id', 'created_at', 'updated_at']
    },
    'lab_results': {
        'fields': [
            'test_name',
            'result_value',
            'result_unit',
            'reference_range',
            'interpretation',
            'performing_lab',
            'ordered_by',
            'comments',
        ],
        'exclude': ['id', 'patient_id', 'order_id', 'collected_at', 'resulted_at']
    },
    'messages': {
        'fields': [
            'subject',
            'body',
        ],
        'exclude': ['id', 'sender_id', 'recipient_id', 'created_at', 'read_at']
    },
    'insurance': {
        'fields': [
            'payer_name',
            'plan_name',
            'policy_number',
            'group_number',
            'subscriber_name',
            'subscriber_id',
            'subscriber_dob',
            'subscriber_relationship',
        ],
        'exclude': ['id', 'patient_id', 'created_at', 'updated_at', 'is_active']
    },
    'audit_logs': {
        'fields': [
            'user_name',
            'patient_name',
            'ip_address',
            'description',
            'old_value',
            'new_value',
        ],
        'exclude': ['id', 'user_id', 'patient_id', 'created_at', 'action']
    }
}


def create_app():
    """Create Flask app with database configuration."""
    app = Flask(__name__)
    
    database_url = os.environ.get('DATABASE_URL', 'sqlite:///epic_ehr.db')
    app.config['SQLALCHEMY_DATABASE_URI'] = database_url
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    
    db.init_app(app)
    return app


def analyze_encryption_status(app) -> Dict[str, Any]:
    """
    Analyze the current encryption status of all tables.
    
    Returns:
        Dictionary with analysis results per table
    """
    results = {}
    encryption = get_encryption()
    
    with app.app_context():
        for table_name, config in ENCRYPTION_SCHEMA.items():
            try:
                # Check if table exists
                if not db.engine.dialect.has_table(db.engine.connect(), table_name):
                    results[table_name] = {
                        'status': 'missing',
                        'message': 'Table does not exist'
                    }
                    continue
                
                # Count total records
                total_query = f"SELECT COUNT(*) FROM {table_name}"
                total_count = db.session.execute(db.text(total_query)).scalar()
                
                # Sample first record to check encryption status
                sample_query = f"SELECT * FROM {table_name} LIMIT 1"
                sample = db.session.execute(db.text(sample_query)).fetchone()
                
                encrypted_count = 0
                unencrypted_count = 0
                
                if sample:
                    # Check each field in sample
                    for field in config.get('fields', []):
                        if hasattr(sample, field):
                            value = getattr(sample, field)
                            if value and encryption.is_encrypted(str(value)):
                                encrypted_count += 1
                            elif value:
                                unencrypted_count += 1
                
                status = 'encrypted' if unencrypted_count == 0 and encrypted_count > 0 else \
                         'partial' if encrypted_count > 0 else \
                         'unencrypted'
                
                results[table_name] = {
                    'status': status,
                    'total_records': total_count,
                    'fields_to_encrypt': len(config.get('fields', [])),
                    'encrypted_fields_found': encrypted_count,
                    'unencrypted_fields_found': unencrypted_count
                }
                
            except Exception as e:
                results[table_name] = {
                    'status': 'error',
                    'message': str(e)
                }
    
    return results


def encrypt_table(app, table_name: str, config: Dict, dry_run: bool = False) -> Dict[str, Any]:
    """
    Encrypt all PHI/PII fields in a table.
    
    Args:
        app: Flask application
        table_name: Name of the table to encrypt
        config: Encryption configuration for the table
        dry_run: If True, don't actually modify data
        
    Returns:
        Dictionary with encryption results
    """
    results = {
        'table': table_name,
        'records_processed': 0,
        'fields_encrypted': 0,
        'errors': []
    }
    
    encryption = get_encryption()
    fields = config.get('fields', [])
    searchable_fields = config.get('searchable_fields', [])
    
    with app.app_context():
        try:
            # Check if table exists
            if not db.engine.dialect.has_table(db.engine.connect(), table_name):
                results['errors'].append(f"Table {table_name} does not exist")
                return results
            
            # Get all records
            select_query = f"SELECT * FROM {table_name}"
            records = db.session.execute(db.text(select_query)).fetchall()
            
            for record in records:
                record_id = record.id if hasattr(record, 'id') else None
                if not record_id:
                    continue
                
                updates = []
                values = {'id': record_id}
                
                for field in fields:
                    if not hasattr(record, field):
                        continue
                    
                    value = getattr(record, field)
                    
                    # Skip if already encrypted or empty
                    if not value or encryption.is_encrypted(str(value)):
                        continue
                    
                    # Encrypt the value
                    encrypted_value = encrypt_field(value)
                    updates.append(f"{field} = :{field}")
                    values[field] = encrypted_value
                    results['fields_encrypted'] += 1
                
                # Add searchable hash for searchable fields
                for field in searchable_fields:
                    if not hasattr(record, field):
                        continue
                    
                    value = getattr(record, field)
                    if value:
                        hash_field = f"{field}_hash"
                        search_hash = hash_for_search(str(value))
                        updates.append(f"{hash_field} = :{hash_field}")
                        values[hash_field] = search_hash
                
                # Update record
                if updates and not dry_run:
                    update_query = f"UPDATE {table_name} SET {', '.join(updates)} WHERE id = :id"
                    try:
                        db.session.execute(db.text(update_query), values)
                    except Exception as e:
                        results['errors'].append(f"Record {record_id}: {str(e)}")
                
                results['records_processed'] += 1
            
            if not dry_run:
                db.session.commit()
                
        except Exception as e:
            results['errors'].append(str(e))
            db.session.rollback()
    
    return results


def verify_encryption(app, table_name: str, config: Dict) -> Dict[str, Any]:
    """
    Verify that encryption/decryption works correctly for a table.
    
    Returns:
        Dictionary with verification results
    """
    results = {
        'table': table_name,
        'verified': True,
        'records_checked': 0,
        'errors': []
    }
    
    encryption = get_encryption()
    fields = config.get('fields', [])
    
    with app.app_context():
        try:
            # Get sample records
            select_query = f"SELECT * FROM {table_name} LIMIT 10"
            records = db.session.execute(db.text(select_query)).fetchall()
            
            for record in records:
                for field in fields:
                    if not hasattr(record, field):
                        continue
                    
                    value = getattr(record, field)
                    if not value:
                        continue
                    
                    if encryption.is_encrypted(str(value)):
                        # Try to decrypt
                        try:
                            decrypted = decrypt_field(str(value))
                            if not decrypted:
                                results['errors'].append(
                                    f"Record {record.id if hasattr(record, 'id') else '?'}, "
                                    f"field {field}: Decryption returned empty"
                                )
                                results['verified'] = False
                        except Exception as e:
                            results['errors'].append(
                                f"Record {record.id if hasattr(record, 'id') else '?'}, "
                                f"field {field}: {str(e)}"
                            )
                            results['verified'] = False
                
                results['records_checked'] += 1
                
        except Exception as e:
            results['errors'].append(str(e))
            results['verified'] = False
    
    return results


def main():
    """Main entry point for the encryption migration script."""
    parser = argparse.ArgumentParser(
        description='HIPAA-compliant database encryption migration tool'
    )
    parser.add_argument(
        '--analyze', 
        action='store_true',
        help='Analyze current encryption status'
    )
    parser.add_argument(
        '--encrypt',
        action='store_true', 
        help='Encrypt all PHI/PII fields'
    )
    parser.add_argument(
        '--verify',
        action='store_true',
        help='Verify encryption integrity'
    )
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='Show what would be encrypted without making changes'
    )
    parser.add_argument(
        '--table',
        type=str,
        help='Process only a specific table'
    )
    
    args = parser.parse_args()
    
    if not any([args.analyze, args.encrypt, args.verify]):
        parser.print_help()
        return
    
    print("=" * 60)
    print("HIPAA-Compliant Database Encryption Tool")
    print("AES-256-GCM with Per-Record Salt and Nonce")
    print("=" * 60)
    print()
    
    # Test encryption system
    print("Testing encryption system...")
    try:
        encryption = get_encryption()
        test_value = "Test Patient Name - SSN: 123-45-6789"
        encrypted = encryption.encrypt(test_value)
        decrypted = encryption.decrypt(encrypted)
        
        if decrypted == test_value:
            print("✓ Encryption system working correctly")
        else:
            print("✗ Encryption test FAILED - data mismatch")
            return
    except Exception as e:
        print(f"✗ Encryption test FAILED: {e}")
        return
    
    print()
    
    # Create Flask app
    app = create_app()
    
    tables = {args.table: ENCRYPTION_SCHEMA[args.table]} if args.table else ENCRYPTION_SCHEMA
    
    if args.analyze:
        print("Analyzing encryption status...")
        print("-" * 40)
        
        results = analyze_encryption_status(app)
        
        for table_name, status in results.items():
            icon = {
                'encrypted': '✓',
                'partial': '◐',
                'unencrypted': '○',
                'missing': '?',
                'error': '✗'
            }.get(status.get('status', 'error'), '?')
            
            print(f"{icon} {table_name}: {status.get('status', 'unknown')}")
            
            if 'total_records' in status:
                print(f"    Records: {status['total_records']}")
                print(f"    Fields to encrypt: {status.get('fields_to_encrypt', 0)}")
            
            if status.get('message'):
                print(f"    Message: {status['message']}")
        
        print()
    
    if args.encrypt:
        if args.dry_run:
            print("DRY RUN - No changes will be made")
        else:
            print("⚠️  WARNING: This will encrypt data in place!")
            print("Make sure you have a backup before proceeding.")
            confirm = input("Type 'ENCRYPT' to continue: ")
            
            if confirm != 'ENCRYPT':
                print("Aborted.")
                return
        
        print()
        print("Encrypting tables...")
        print("-" * 40)
        
        for table_name, config in tables.items():
            print(f"Processing {table_name}...")
            results = encrypt_table(app, table_name, config, dry_run=args.dry_run)
            
            print(f"    Records: {results['records_processed']}")
            print(f"    Fields encrypted: {results['fields_encrypted']}")
            
            if results['errors']:
                print(f"    Errors: {len(results['errors'])}")
                for error in results['errors'][:3]:
                    print(f"      - {error}")
        
        print()
        print("Encryption complete!" if not args.dry_run else "Dry run complete!")
    
    if args.verify:
        print("Verifying encryption...")
        print("-" * 40)
        
        all_verified = True
        
        for table_name, config in tables.items():
            results = verify_encryption(app, table_name, config)
            
            icon = '✓' if results['verified'] else '✗'
            print(f"{icon} {table_name}")
            
            if not results['verified']:
                all_verified = False
                for error in results['errors'][:3]:
                    print(f"    - {error}")
        
        print()
        if all_verified:
            print("✓ All encryption verified successfully!")
        else:
            print("✗ Some verification errors found - check above")


if __name__ == '__main__':
    main()
