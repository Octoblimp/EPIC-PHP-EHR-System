"""
Database Models for Epic EHR System
"""
from flask_sqlalchemy import SQLAlchemy

db = SQLAlchemy()

# Import all models for easy access
from models.patient import Patient, Allergy
from models.encounter import Encounter, StickyNote
from models.insurance import InsurancePayer, InsuranceCoverage, InsuranceAuthorization, EligibilityCheck
from models.note import Note, NoteAddendum, SmartPhrase
from models.medication import Medication
from models.order import Order
from models.vital import Vital
from models.flowsheet import FlowsheetEntry
from models.lab import LabResult
from models.audit import AuditLog
from models.user import User
from models.auth import Session
from models.role import Role, Permission
from models.scheduling import Appointment
from models.theme import Theme
from models.non_clinical import Message
