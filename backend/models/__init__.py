"""
Database Models for Epic EHR System
"""
from flask_sqlalchemy import SQLAlchemy

db = SQLAlchemy()

# Import all models for easy access
from models.patient import Patient, Allergy
from models.encounter import Encounter, StickyNote
from models.insurance import InsurancePayer, InsuranceCoverage, InsuranceAuthorization, EligibilityCheck
from models.clearinghouse import ClearinghouseConfig, EligibilityTransaction, CLEARINGHOUSE_PROVIDERS
from models.theme import Theme
from models.non_clinical import Message
from models.shortcode import Shortcode, PageAccess

