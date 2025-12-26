"""
Scheduling Models
Appointment scheduling, provider calendars, resource booking
SECURITY: PHI/PII is automatically encrypted at rest
"""
from datetime import datetime, timedelta, date, time
from . import db
from utils.encryption import EncryptedString, EncryptedText
import json


class Appointment(db.Model):
    """Appointment/scheduling model - ENCRYPTED"""
    __tablename__ = 'appointments'
    
    id = db.Column(db.Integer, primary_key=True)
    
    # Patient
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False, index=True)
    
    # Provider/Resource
    provider_id = db.Column(db.Integer, db.ForeignKey('users.id'), index=True)
    resource_id = db.Column(db.Integer, db.ForeignKey('scheduling_resources.id'))
    
    # Location
    facility_id = db.Column(db.Integer, db.ForeignKey('facilities.id'))
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'))
    room_id = db.Column(db.Integer, db.ForeignKey('rooms.id'))
    
    # Appointment Type
    appointment_type_id = db.Column(db.Integer, db.ForeignKey('appointment_types.id'), nullable=False)
    
    # Timing
    scheduled_date = db.Column(db.Date, nullable=False, index=True)
    scheduled_time = db.Column(db.Time, nullable=False)
    duration_minutes = db.Column(db.Integer, nullable=False, default=30)
    end_time = db.Column(db.Time)
    
    # Status
    status = db.Column(db.String(20), default='scheduled', index=True)
    # scheduled, confirmed, checked_in, in_progress, completed, cancelled, no_show, rescheduled
    
    # Check-in/out
    checked_in_at = db.Column(db.DateTime)
    checked_in_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    roomed_at = db.Column(db.DateTime)
    visit_started_at = db.Column(db.DateTime)
    visit_ended_at = db.Column(db.DateTime)
    checked_out_at = db.Column(db.DateTime)
    
    # Details - ENCRYPTED (clinical PHI)
    reason_for_visit = db.Column(EncryptedString(500))
    chief_complaint = db.Column(EncryptedString(500))
    notes = db.Column(EncryptedText())
    special_instructions = db.Column(EncryptedText())
    
    # Insurance/Authorization - ENCRYPTED
    insurance_verified = db.Column(db.Boolean, default=False)
    authorization_number = db.Column(EncryptedString(50))
    copay_amount = db.Column(db.Numeric(10, 2))
    copay_collected = db.Column(db.Boolean, default=False)
    
    # Recurrence
    is_recurring = db.Column(db.Boolean, default=False)
    recurrence_pattern_id = db.Column(db.Integer, db.ForeignKey('recurrence_patterns.id'))
    parent_appointment_id = db.Column(db.Integer, db.ForeignKey('appointments.id'))
    
    # Cancellation - ENCRYPTED
    cancelled_at = db.Column(db.DateTime)
    cancelled_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    cancellation_reason = db.Column(EncryptedString(500))
    
    # Encounter link
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Audit
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    updated_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    # Relationships
    patient = db.relationship('Patient', backref='appointments')
    provider = db.relationship('User', foreign_keys=[provider_id], backref='provider_appointments')
    appointment_type = db.relationship('AppointmentType', backref='appointments')
    
    @property
    def scheduled_datetime(self):
        """Get combined datetime"""
        return datetime.combine(self.scheduled_date, self.scheduled_time)
    
    @property
    def end_datetime(self):
        """Get end datetime"""
        return self.scheduled_datetime + timedelta(minutes=self.duration_minutes)
    
    def check_in(self, user_id):
        """Check in patient"""
        self.status = 'checked_in'
        self.checked_in_at = datetime.utcnow()
        self.checked_in_by = user_id
    
    def room_patient(self):
        """Mark patient as roomed"""
        self.status = 'in_progress'
        self.roomed_at = datetime.utcnow()
    
    def start_visit(self):
        """Start the visit"""
        self.visit_started_at = datetime.utcnow()
    
    def end_visit(self):
        """End the visit"""
        self.visit_ended_at = datetime.utcnow()
    
    def check_out(self):
        """Check out patient"""
        self.status = 'completed'
        self.checked_out_at = datetime.utcnow()
    
    def cancel(self, user_id, reason=None):
        """Cancel appointment"""
        self.status = 'cancelled'
        self.cancelled_at = datetime.utcnow()
        self.cancelled_by = user_id
        self.cancellation_reason = reason
    
    def mark_no_show(self):
        """Mark as no-show"""
        self.status = 'no_show'
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'provider_id': self.provider_id,
            'appointment_type_id': self.appointment_type_id,
            'scheduled_date': self.scheduled_date.isoformat() if self.scheduled_date else None,
            'scheduled_time': self.scheduled_time.isoformat() if self.scheduled_time else None,
            'duration_minutes': self.duration_minutes,
            'status': self.status,
            'reason_for_visit': self.reason_for_visit,
            'checked_in_at': self.checked_in_at.isoformat() if self.checked_in_at else None,
            'facility_id': self.facility_id,
            'department_id': self.department_id
        }


class AppointmentType(db.Model):
    """Types of appointments (New Patient, Follow-up, etc.)"""
    __tablename__ = 'appointment_types'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    code = db.Column(db.String(20), unique=True)
    description = db.Column(db.Text)
    
    # Duration
    default_duration_minutes = db.Column(db.Integer, default=30)
    min_duration_minutes = db.Column(db.Integer, default=15)
    max_duration_minutes = db.Column(db.Integer, default=120)
    
    # Settings
    color = db.Column(db.String(7), default='#0066cc')  # For calendar display
    is_active = db.Column(db.Boolean, default=True)
    requires_authorization = db.Column(db.Boolean, default=False)
    is_telehealth = db.Column(db.Boolean, default=False)
    
    # Allowed settings
    allow_self_schedule = db.Column(db.Boolean, default=False)
    allow_waitlist = db.Column(db.Boolean, default=True)
    max_per_day = db.Column(db.Integer)  # Limit per provider per day
    
    # Preparation
    prep_time_minutes = db.Column(db.Integer, default=0)
    cleanup_time_minutes = db.Column(db.Integer, default=0)
    
    # Department restrictions
    department_ids = db.Column(db.Text)  # JSON array
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'code': self.code,
            'default_duration_minutes': self.default_duration_minutes,
            'color': self.color,
            'is_active': self.is_active,
            'is_telehealth': self.is_telehealth
        }


class ProviderSchedule(db.Model):
    """Provider availability template"""
    __tablename__ = 'provider_schedules'
    
    id = db.Column(db.Integer, primary_key=True)
    provider_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    
    # Schedule type
    schedule_type = db.Column(db.String(20), default='regular')  # regular, override, block
    
    # Day of week (0=Sunday, 6=Saturday) for regular schedules
    day_of_week = db.Column(db.Integer)
    
    # Or specific date for overrides/blocks
    specific_date = db.Column(db.Date)
    
    # Time range
    start_time = db.Column(db.Time, nullable=False)
    end_time = db.Column(db.Time, nullable=False)
    
    # Location
    facility_id = db.Column(db.Integer, db.ForeignKey('facilities.id'))
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'))
    
    # Slot settings
    slot_duration_minutes = db.Column(db.Integer, default=30)
    slots_per_hour = db.Column(db.Integer, default=2)
    max_overbooking = db.Column(db.Integer, default=0)
    
    # Restrictions
    appointment_type_ids = db.Column(db.Text)  # JSON array of allowed types
    
    # Effective dates
    effective_start_date = db.Column(db.Date)
    effective_end_date = db.Column(db.Date)
    
    is_active = db.Column(db.Boolean, default=True)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'provider_id': self.provider_id,
            'schedule_type': self.schedule_type,
            'day_of_week': self.day_of_week,
            'specific_date': self.specific_date.isoformat() if self.specific_date else None,
            'start_time': self.start_time.isoformat() if self.start_time else None,
            'end_time': self.end_time.isoformat() if self.end_time else None,
            'slot_duration_minutes': self.slot_duration_minutes,
            'is_active': self.is_active
        }


class SchedulingResource(db.Model):
    """Schedulable resources (rooms, equipment, etc.)"""
    __tablename__ = 'scheduling_resources'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    code = db.Column(db.String(20))
    resource_type = db.Column(db.String(50))  # room, equipment, service
    
    # Location
    facility_id = db.Column(db.Integer, db.ForeignKey('facilities.id'))
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'))
    
    # Capacity
    capacity = db.Column(db.Integer, default=1)
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    
    # Settings
    requires_cleanup = db.Column(db.Boolean, default=False)
    cleanup_minutes = db.Column(db.Integer, default=0)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'resource_type': self.resource_type,
            'facility_id': self.facility_id,
            'is_active': self.is_active
        }


class Room(db.Model):
    """Physical rooms for scheduling"""
    __tablename__ = 'rooms'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    room_number = db.Column(db.String(20))
    
    # Location
    facility_id = db.Column(db.Integer, db.ForeignKey('facilities.id'))
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'))
    floor = db.Column(db.String(20))
    building = db.Column(db.String(100))
    
    # Type
    room_type = db.Column(db.String(50))  # exam, procedure, conference, etc.
    
    # Features
    has_equipment = db.Column(db.Text)  # JSON array of equipment
    capacity = db.Column(db.Integer, default=1)
    is_ada_accessible = db.Column(db.Boolean, default=True)
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    is_available = db.Column(db.Boolean, default=True)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'room_number': self.room_number,
            'room_type': self.room_type,
            'department_id': self.department_id,
            'is_available': self.is_available
        }


class Waitlist(db.Model):
    """Appointment waitlist"""
    __tablename__ = 'waitlist'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    
    # Preferences
    provider_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    appointment_type_id = db.Column(db.Integer, db.ForeignKey('appointment_types.id'))
    facility_id = db.Column(db.Integer, db.ForeignKey('facilities.id'))
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'))
    
    # Date preferences
    preferred_date_start = db.Column(db.Date)
    preferred_date_end = db.Column(db.Date)
    preferred_time_start = db.Column(db.Time)
    preferred_time_end = db.Column(db.Time)
    preferred_days = db.Column(db.String(50))  # "1,2,3,4,5" for weekdays
    
    # Priority
    priority = db.Column(db.Integer, default=5)  # 1=highest
    is_urgent = db.Column(db.Boolean, default=False)
    
    # Reason
    reason_for_visit = db.Column(db.String(500))
    notes = db.Column(db.Text)
    
    # Status
    status = db.Column(db.String(20), default='active')  # active, scheduled, cancelled, expired
    
    # Contact preferences
    contact_phone = db.Column(db.String(20))
    contact_email = db.Column(db.String(255))
    contact_method = db.Column(db.String(20), default='phone')
    
    # Outcome
    scheduled_appointment_id = db.Column(db.Integer, db.ForeignKey('appointments.id'))
    
    # Audit
    added_at = db.Column(db.DateTime, default=datetime.utcnow)
    added_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'provider_id': self.provider_id,
            'appointment_type_id': self.appointment_type_id,
            'preferred_date_start': self.preferred_date_start.isoformat() if self.preferred_date_start else None,
            'preferred_date_end': self.preferred_date_end.isoformat() if self.preferred_date_end else None,
            'priority': self.priority,
            'status': self.status,
            'added_at': self.added_at.isoformat() if self.added_at else None
        }


class RecurrencePattern(db.Model):
    """Recurrence patterns for recurring appointments"""
    __tablename__ = 'recurrence_patterns'
    
    id = db.Column(db.Integer, primary_key=True)
    
    # Pattern
    frequency = db.Column(db.String(20), nullable=False)  # daily, weekly, biweekly, monthly
    interval = db.Column(db.Integer, default=1)  # Every X days/weeks/months
    
    # Weekly specifics
    days_of_week = db.Column(db.String(20))  # "1,3,5" for Mon, Wed, Fri
    
    # Monthly specifics
    day_of_month = db.Column(db.Integer)  # 1-31
    week_of_month = db.Column(db.Integer)  # 1-5 (5 = last)
    
    # End conditions
    end_type = db.Column(db.String(20))  # date, count, never
    end_date = db.Column(db.Date)
    occurrence_count = db.Column(db.Integer)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'frequency': self.frequency,
            'interval': self.interval,
            'end_type': self.end_type,
            'end_date': self.end_date.isoformat() if self.end_date else None,
            'occurrence_count': self.occurrence_count
        }
