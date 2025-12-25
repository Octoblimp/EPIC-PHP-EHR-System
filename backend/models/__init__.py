"""
Database Models for Epic EHR System
"""
from flask_sqlalchemy import SQLAlchemy

db = SQLAlchemy()

# Import all models for easy access
# Note: Import order matters due to foreign key dependencies

# Core organization models (no dependencies)
from models.theme import Organization, Department, Facility, Bed, Theme, SystemSetting

# User models (depends on roles)
from models.role import Role, Permission, RolePermission, UserDepartment, PatientList, PatientListEntry

# Patient models
from models.patient import Patient, Allergy

# Encounter models (depends on patients)
from models.encounter import Encounter, StickyNote

# User model (depends on roles)
from models.user import User

# Auth models (depends on users)
from models.auth import UserSession

# Insurance models (depends on patients)
from models.insurance import InsurancePayer, InsuranceCoverage, InsuranceAuthorization, EligibilityCheck

# Clearinghouse models (depends on insurance)
from models.clearinghouse import ClearinghouseConfig, EligibilityTransaction, CLEARINGHOUSE_PROVIDERS

# Clinical models (depends on patients, encounters)
from models.medication import Medication, MedicationAdministration
from models.order import Order, OrderSet
from models.vital import Vital
from models.flowsheet import FlowsheetTemplate, FlowsheetEntry, FlowsheetSection
from models.lab import LabResult
from models.note import Note, SmartPhrase

# Non-clinical models (depends on patients, users, insurance)
from models.non_clinical import BillingAccount, Charge, Claim, Payment, MessageThread, Message

# Audit models (depends on users)
from models.audit import AuditLog

# Scheduling models (depends on patients, users, facilities)
from models.scheduling import Appointment, AppointmentType, ProviderSchedule, SchedulingResource, Room, Waitlist, RecurrencePattern

# Shortcode models (depends on roles)
from models.shortcode import Shortcode, PageAccess

