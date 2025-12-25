"""
Theme and Organization Configuration Models
Allows hospitals/clinics to customize their Epic instance
"""
from . import db
from datetime import datetime


class Organization(db.Model):
    """Healthcare organization (hospital, clinic, practice)"""
    __tablename__ = 'organizations'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    short_name = db.Column(db.String(50))
    org_type = db.Column(db.String(50))  # hospital, clinic, practice, health_system
    parent_org_id = db.Column(db.Integer, db.ForeignKey('organizations.id'))
    
    # Contact info
    address = db.Column(db.String(500))
    city = db.Column(db.String(100))
    state = db.Column(db.String(50))
    zip_code = db.Column(db.String(20))
    phone = db.Column(db.String(30))
    fax = db.Column(db.String(30))
    
    # Identifiers
    npi = db.Column(db.String(20))
    tax_id = db.Column(db.String(20))
    
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    departments = db.relationship('Department', backref='organization', lazy='dynamic')
    theme = db.relationship('Theme', backref='organization', uselist=False)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'short_name': self.short_name,
            'org_type': self.org_type,
            'address': self.address,
            'city': self.city,
            'state': self.state,
            'zip_code': self.zip_code,
            'phone': self.phone,
            'is_active': self.is_active
        }


class Department(db.Model):
    """Hospital department/unit"""
    __tablename__ = 'departments'
    
    id = db.Column(db.Integer, primary_key=True)
    organization_id = db.Column(db.Integer, db.ForeignKey('organizations.id'), nullable=False)
    name = db.Column(db.String(200), nullable=False)
    short_name = db.Column(db.String(50))
    dept_type = db.Column(db.String(50))  # nursing_unit, clinic, ed, or, lab, radiology, pharmacy
    
    # Unit details
    unit_code = db.Column(db.String(20))
    floor = db.Column(db.String(20))
    building = db.Column(db.String(100))
    bed_count = db.Column(db.Integer)
    
    # Contact
    phone = db.Column(db.String(30))
    extension = db.Column(db.String(10))
    
    # Specialty
    specialty = db.Column(db.String(100))  # surgical, medical, pediatric, ob, icu, etc.
    
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    beds = db.relationship('Bed', backref='department', lazy='dynamic')
    
    def to_dict(self):
        return {
            'id': self.id,
            'organization_id': self.organization_id,
            'name': self.name,
            'short_name': self.short_name,
            'dept_type': self.dept_type,
            'unit_code': self.unit_code,
            'floor': self.floor,
            'building': self.building,
            'bed_count': self.bed_count,
            'specialty': self.specialty,
            'is_active': self.is_active
        }


class Facility(db.Model):
    """Healthcare facility/location (hospital campus, clinic building, etc.)"""
    __tablename__ = 'facilities'
    
    id = db.Column(db.Integer, primary_key=True)
    organization_id = db.Column(db.Integer, db.ForeignKey('organizations.id'), nullable=False)
    name = db.Column(db.String(200), nullable=False)
    short_name = db.Column(db.String(50))
    facility_type = db.Column(db.String(50))  # hospital, clinic, urgent_care, surgery_center, etc.
    
    # Location
    address = db.Column(db.String(500))
    city = db.Column(db.String(100))
    state = db.Column(db.String(50))
    zip_code = db.Column(db.String(20))
    
    # Contact
    phone = db.Column(db.String(30))
    fax = db.Column(db.String(30))
    
    # Identifiers
    facility_code = db.Column(db.String(20), unique=True)
    npi = db.Column(db.String(20))
    
    # Operations
    is_24_hour = db.Column(db.Boolean, default=False)
    operating_hours = db.Column(db.Text)  # JSON for hours per day
    
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'organization_id': self.organization_id,
            'name': self.name,
            'short_name': self.short_name,
            'facility_type': self.facility_type,
            'address': self.address,
            'city': self.city,
            'state': self.state,
            'zip_code': self.zip_code,
            'phone': self.phone,
            'is_active': self.is_active
        }


class Bed(db.Model):
    """Hospital bed"""
    __tablename__ = 'beds'
    
    id = db.Column(db.Integer, primary_key=True)
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'), nullable=False)
    room_number = db.Column(db.String(20), nullable=False)
    bed_letter = db.Column(db.String(5))  # A, B, C for semi-private
    
    bed_type = db.Column(db.String(50))  # standard, icu, bariatric, pediatric, ob
    status = db.Column(db.String(30), default='available')  # available, occupied, cleaning, blocked
    
    # Current patient (if occupied)
    current_patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'))
    current_encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    is_active = db.Column(db.Boolean, default=True)
    
    def to_dict(self):
        return {
            'id': self.id,
            'department_id': self.department_id,
            'room_number': self.room_number,
            'bed_letter': self.bed_letter,
            'room_bed': f"{self.room_number}{self.bed_letter or ''}",
            'bed_type': self.bed_type,
            'status': self.status,
            'current_patient_id': self.current_patient_id,
            'is_active': self.is_active
        }


class Theme(db.Model):
    """Customizable UI theme for organization"""
    __tablename__ = 'themes'
    
    id = db.Column(db.Integer, primary_key=True)
    organization_id = db.Column(db.Integer, db.ForeignKey('organizations.id'), unique=True)
    name = db.Column(db.String(100), default='Default')
    
    # Primary colors
    primary_color = db.Column(db.String(7), default='#c00')  # Epic red
    secondary_color = db.Column(db.String(7), default='#0078d4')  # Blue
    accent_color = db.Column(db.String(7), default='#107c10')  # Green
    
    # Header/Navigation
    header_bg_color = db.Column(db.String(7), default='#c00')
    header_text_color = db.Column(db.String(7), default='#ffffff')
    nav_bg_color = db.Column(db.String(7), default='#f5f5f5')
    nav_text_color = db.Column(db.String(7), default='#333333')
    nav_active_bg = db.Column(db.String(7), default='#e3f2fd')
    
    # Patient header
    patient_header_bg = db.Column(db.String(7), default='#e8f4fd')
    patient_header_text = db.Column(db.String(7), default='#1a1a1a')
    
    # Buttons
    button_primary_bg = db.Column(db.String(7), default='#0078d4')
    button_primary_text = db.Column(db.String(7), default='#ffffff')
    button_secondary_bg = db.Column(db.String(7), default='#6c757d')
    button_secondary_text = db.Column(db.String(7), default='#ffffff')
    button_danger_bg = db.Column(db.String(7), default='#dc3545')
    
    # Status colors
    status_critical_color = db.Column(db.String(7), default='#dc3545')
    status_warning_color = db.Column(db.String(7), default='#ffc107')
    status_success_color = db.Column(db.String(7), default='#28a745')
    status_info_color = db.Column(db.String(7), default='#17a2b8')
    
    # Fonts
    font_family = db.Column(db.String(200), default="'Segoe UI', Tahoma, Geneva, Verdana, sans-serif")
    font_size_base = db.Column(db.String(10), default='13px')
    
    # Logo
    logo_url = db.Column(db.String(500))
    logo_width = db.Column(db.String(20), default='120px')
    favicon_url = db.Column(db.String(500))
    
    # Custom CSS overrides
    custom_css = db.Column(db.Text)
    
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'organization_id': self.organization_id,
            'name': self.name,
            'primary_color': self.primary_color,
            'secondary_color': self.secondary_color,
            'accent_color': self.accent_color,
            'header_bg_color': self.header_bg_color,
            'header_text_color': self.header_text_color,
            'nav_bg_color': self.nav_bg_color,
            'nav_text_color': self.nav_text_color,
            'nav_active_bg': self.nav_active_bg,
            'patient_header_bg': self.patient_header_bg,
            'patient_header_text': self.patient_header_text,
            'button_primary_bg': self.button_primary_bg,
            'button_primary_text': self.button_primary_text,
            'button_secondary_bg': self.button_secondary_bg,
            'button_danger_bg': self.button_danger_bg,
            'status_critical_color': self.status_critical_color,
            'status_warning_color': self.status_warning_color,
            'status_success_color': self.status_success_color,
            'status_info_color': self.status_info_color,
            'font_family': self.font_family,
            'font_size_base': self.font_size_base,
            'logo_url': self.logo_url,
            'logo_width': self.logo_width,
            'custom_css': self.custom_css
        }
    
    def to_css_variables(self):
        """Generate CSS custom properties from theme"""
        return f"""
:root {{
    --primary-color: {self.primary_color};
    --secondary-color: {self.secondary_color};
    --accent-color: {self.accent_color};
    --header-bg: {self.header_bg_color};
    --header-text: {self.header_text_color};
    --nav-bg: {self.nav_bg_color};
    --nav-text: {self.nav_text_color};
    --nav-active-bg: {self.nav_active_bg};
    --patient-header-bg: {self.patient_header_bg};
    --patient-header-text: {self.patient_header_text};
    --btn-primary-bg: {self.button_primary_bg};
    --btn-primary-text: {self.button_primary_text};
    --btn-secondary-bg: {self.button_secondary_bg};
    --btn-danger-bg: {self.button_danger_bg};
    --status-critical: {self.status_critical_color};
    --status-warning: {self.status_warning_color};
    --status-success: {self.status_success_color};
    --status-info: {self.status_info_color};
    --font-family: {self.font_family};
    --font-size-base: {self.font_size_base};
}}
"""


class SystemSetting(db.Model):
    """System-wide configuration settings"""
    __tablename__ = 'system_settings'
    
    id = db.Column(db.Integer, primary_key=True)
    organization_id = db.Column(db.Integer, db.ForeignKey('organizations.id'))
    category = db.Column(db.String(50), nullable=False)  # general, security, clinical, interface
    setting_key = db.Column(db.String(100), nullable=False)
    setting_value = db.Column(db.Text)
    value_type = db.Column(db.String(20), default='string')  # string, int, bool, json
    description = db.Column(db.String(500))
    is_editable = db.Column(db.Boolean, default=True)
    
    __table_args__ = (
        db.UniqueConstraint('organization_id', 'setting_key', name='unique_org_setting'),
    )
    
    def to_dict(self):
        value = self.setting_value
        if self.value_type == 'int':
            value = int(value) if value else 0
        elif self.value_type == 'bool':
            value = value.lower() in ('true', '1', 'yes') if value else False
        elif self.value_type == 'json':
            import json
            value = json.loads(value) if value else {}
            
        return {
            'id': self.id,
            'category': self.category,
            'key': self.setting_key,
            'value': value,
            'value_type': self.value_type,
            'description': self.description,
            'is_editable': self.is_editable
        }
