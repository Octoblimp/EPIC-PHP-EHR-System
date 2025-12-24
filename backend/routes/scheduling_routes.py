"""
Scheduling Routes
Appointment management, provider calendars, resource booking
"""
from flask import Blueprint, request, jsonify, session, g
from datetime import datetime, date, time, timedelta
from models.scheduling import (
    db, Appointment, AppointmentType, ProviderSchedule,
    SchedulingResource, Room, Waitlist, RecurrencePattern
)
from models.audit import AuditLog
from routes.auth_routes import login_required, permission_required
import json

scheduling_bp = Blueprint('scheduling', __name__, url_prefix='/api/scheduling')


# ============================================================
# APPOINTMENTS
# ============================================================

@scheduling_bp.route('/appointments', methods=['GET'])
@login_required
@permission_required('appointments.view')
def get_appointments():
    """Get appointments with filters"""
    # Parse filters
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')
    provider_id = request.args.get('provider_id', type=int)
    patient_id = request.args.get('patient_id', type=int)
    department_id = request.args.get('department_id', type=int)
    status = request.args.get('status')
    appointment_type_id = request.args.get('appointment_type_id', type=int)
    
    query = Appointment.query
    
    # Date range filter
    if start_date:
        query = query.filter(Appointment.scheduled_date >= datetime.strptime(start_date, '%Y-%m-%d').date())
    if end_date:
        query = query.filter(Appointment.scheduled_date <= datetime.strptime(end_date, '%Y-%m-%d').date())
    
    # Other filters
    if provider_id:
        query = query.filter(Appointment.provider_id == provider_id)
    if patient_id:
        query = query.filter(Appointment.patient_id == patient_id)
    if department_id:
        query = query.filter(Appointment.department_id == department_id)
    if status:
        query = query.filter(Appointment.status == status)
    if appointment_type_id:
        query = query.filter(Appointment.appointment_type_id == appointment_type_id)
    
    # Order by date and time
    query = query.order_by(Appointment.scheduled_date, Appointment.scheduled_time)
    
    appointments = query.all()
    
    return jsonify({
        'success': True,
        'appointments': [apt.to_dict() for apt in appointments],
        'count': len(appointments)
    })


@scheduling_bp.route('/appointments/<int:appointment_id>', methods=['GET'])
@login_required
@permission_required('appointments.view')
def get_appointment(appointment_id):
    """Get single appointment details"""
    appointment = Appointment.query.get_or_404(appointment_id)
    
    # Audit log
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='VIEW',
        resource_type='Appointment',
        resource_id=appointment_id
    )
    
    return jsonify({
        'success': True,
        'appointment': appointment.to_dict()
    })


@scheduling_bp.route('/appointments', methods=['POST'])
@login_required
@permission_required('appointments.create')
def create_appointment():
    """Create a new appointment"""
    data = request.get_json()
    
    # Validate required fields
    required = ['patient_id', 'appointment_type_id', 'scheduled_date', 'scheduled_time']
    for field in required:
        if field not in data:
            return jsonify({'success': False, 'error': f'Missing required field: {field}'}), 400
    
    # Check for conflicts
    conflicts = check_scheduling_conflicts(
        provider_id=data.get('provider_id'),
        room_id=data.get('room_id'),
        scheduled_date=data['scheduled_date'],
        scheduled_time=data['scheduled_time'],
        duration=data.get('duration_minutes', 30)
    )
    
    if conflicts and not data.get('force_overbook'):
        return jsonify({
            'success': False,
            'error': 'Scheduling conflict detected',
            'conflicts': conflicts
        }), 409
    
    # Get appointment type for default duration
    apt_type = AppointmentType.query.get(data['appointment_type_id'])
    duration = data.get('duration_minutes', apt_type.default_duration_minutes if apt_type else 30)
    
    # Create appointment
    appointment = Appointment(
        patient_id=data['patient_id'],
        provider_id=data.get('provider_id'),
        resource_id=data.get('resource_id'),
        facility_id=data.get('facility_id'),
        department_id=data.get('department_id'),
        room_id=data.get('room_id'),
        appointment_type_id=data['appointment_type_id'],
        scheduled_date=datetime.strptime(data['scheduled_date'], '%Y-%m-%d').date(),
        scheduled_time=datetime.strptime(data['scheduled_time'], '%H:%M').time(),
        duration_minutes=duration,
        reason_for_visit=data.get('reason_for_visit'),
        chief_complaint=data.get('chief_complaint'),
        notes=data.get('notes'),
        special_instructions=data.get('special_instructions'),
        is_recurring=data.get('is_recurring', False),
        created_by=g.current_user.id
    )
    
    db.session.add(appointment)
    
    # Handle recurring appointments
    if data.get('is_recurring') and data.get('recurrence_pattern'):
        create_recurring_appointments(appointment, data['recurrence_pattern'])
    
    db.session.commit()
    
    # Audit log
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='CREATE',
        resource_type='Appointment',
        resource_id=appointment.id,
        new_values=appointment.to_dict()
    )
    
    return jsonify({
        'success': True,
        'appointment': appointment.to_dict()
    }), 201


@scheduling_bp.route('/appointments/<int:appointment_id>', methods=['PUT'])
@login_required
@permission_required('appointments.edit')
def update_appointment(appointment_id):
    """Update an appointment"""
    appointment = Appointment.query.get_or_404(appointment_id)
    data = request.get_json()
    
    old_values = appointment.to_dict()
    
    # Check for conflicts if date/time changed
    if 'scheduled_date' in data or 'scheduled_time' in data:
        conflicts = check_scheduling_conflicts(
            provider_id=data.get('provider_id', appointment.provider_id),
            room_id=data.get('room_id', appointment.room_id),
            scheduled_date=data.get('scheduled_date', appointment.scheduled_date.isoformat()),
            scheduled_time=data.get('scheduled_time', appointment.scheduled_time.isoformat()),
            duration=data.get('duration_minutes', appointment.duration_minutes),
            exclude_appointment_id=appointment_id
        )
        
        if conflicts and not data.get('force_overbook'):
            return jsonify({
                'success': False,
                'error': 'Scheduling conflict detected',
                'conflicts': conflicts
            }), 409
    
    # Update fields
    updateable = [
        'provider_id', 'resource_id', 'facility_id', 'department_id', 'room_id',
        'appointment_type_id', 'duration_minutes', 'reason_for_visit',
        'chief_complaint', 'notes', 'special_instructions', 'insurance_verified',
        'authorization_number', 'copay_amount'
    ]
    
    for field in updateable:
        if field in data:
            setattr(appointment, field, data[field])
    
    # Date/time updates
    if 'scheduled_date' in data:
        appointment.scheduled_date = datetime.strptime(data['scheduled_date'], '%Y-%m-%d').date()
    if 'scheduled_time' in data:
        appointment.scheduled_time = datetime.strptime(data['scheduled_time'], '%H:%M').time()
    
    appointment.updated_by = g.current_user.id
    db.session.commit()
    
    # Audit log
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='UPDATE',
        resource_type='Appointment',
        resource_id=appointment_id,
        old_values=old_values,
        new_values=appointment.to_dict()
    )
    
    return jsonify({
        'success': True,
        'appointment': appointment.to_dict()
    })


@scheduling_bp.route('/appointments/<int:appointment_id>/status', methods=['PUT'])
@login_required
@permission_required('appointments.edit')
def update_appointment_status(appointment_id):
    """Update appointment status (check-in, room, complete, etc.)"""
    appointment = Appointment.query.get_or_404(appointment_id)
    data = request.get_json()
    
    new_status = data.get('status')
    old_status = appointment.status
    
    if new_status == 'checked_in':
        appointment.check_in(g.current_user.id)
    elif new_status == 'in_progress':
        appointment.room_patient()
    elif new_status == 'completed':
        appointment.check_out()
    elif new_status == 'no_show':
        appointment.mark_no_show()
    elif new_status == 'cancelled':
        appointment.cancel(g.current_user.id, data.get('reason'))
    else:
        appointment.status = new_status
    
    db.session.commit()
    
    # Audit log
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='STATUS_CHANGE',
        resource_type='Appointment',
        resource_id=appointment_id,
        old_values={'status': old_status},
        new_values={'status': appointment.status}
    )
    
    return jsonify({
        'success': True,
        'appointment': appointment.to_dict()
    })


# ============================================================
# PROVIDER SCHEDULES
# ============================================================

@scheduling_bp.route('/providers/<int:provider_id>/schedule', methods=['GET'])
@login_required
@permission_required('schedules.view')
def get_provider_schedule(provider_id):
    """Get provider's schedule template"""
    schedules = ProviderSchedule.query.filter_by(
        provider_id=provider_id,
        is_active=True
    ).all()
    
    return jsonify({
        'success': True,
        'schedules': [s.to_dict() for s in schedules]
    })


@scheduling_bp.route('/providers/<int:provider_id>/availability', methods=['GET'])
@login_required
@permission_required('schedules.view')
def get_provider_availability(provider_id):
    """Get provider's available slots for a date range"""
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')
    appointment_type_id = request.args.get('appointment_type_id', type=int)
    
    if not start_date or not end_date:
        return jsonify({'success': False, 'error': 'start_date and end_date required'}), 400
    
    start = datetime.strptime(start_date, '%Y-%m-%d').date()
    end = datetime.strptime(end_date, '%Y-%m-%d').date()
    
    # Get appointment type for duration
    duration = 30
    if appointment_type_id:
        apt_type = AppointmentType.query.get(appointment_type_id)
        if apt_type:
            duration = apt_type.default_duration_minutes
    
    available_slots = []
    current_date = start
    
    while current_date <= end:
        day_slots = get_available_slots_for_day(provider_id, current_date, duration)
        if day_slots:
            available_slots.append({
                'date': current_date.isoformat(),
                'slots': day_slots
            })
        current_date += timedelta(days=1)
    
    return jsonify({
        'success': True,
        'provider_id': provider_id,
        'availability': available_slots
    })


@scheduling_bp.route('/providers/<int:provider_id>/schedule', methods=['POST'])
@login_required
@permission_required('schedules.manage')
def create_provider_schedule(provider_id):
    """Create or update provider schedule"""
    data = request.get_json()
    
    schedule = ProviderSchedule(
        provider_id=provider_id,
        schedule_type=data.get('schedule_type', 'regular'),
        day_of_week=data.get('day_of_week'),
        specific_date=datetime.strptime(data['specific_date'], '%Y-%m-%d').date() if data.get('specific_date') else None,
        start_time=datetime.strptime(data['start_time'], '%H:%M').time(),
        end_time=datetime.strptime(data['end_time'], '%H:%M').time(),
        facility_id=data.get('facility_id'),
        department_id=data.get('department_id'),
        slot_duration_minutes=data.get('slot_duration_minutes', 30),
        slots_per_hour=data.get('slots_per_hour', 2),
        max_overbooking=data.get('max_overbooking', 0),
        effective_start_date=datetime.strptime(data['effective_start_date'], '%Y-%m-%d').date() if data.get('effective_start_date') else None,
        effective_end_date=datetime.strptime(data['effective_end_date'], '%Y-%m-%d').date() if data.get('effective_end_date') else None
    )
    
    db.session.add(schedule)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'schedule': schedule.to_dict()
    }), 201


@scheduling_bp.route('/providers/<int:provider_id>/block', methods=['POST'])
@login_required
@permission_required('schedules.manage')
def block_provider_time(provider_id):
    """Block off time for a provider (vacation, meeting, etc.)"""
    data = request.get_json()
    
    block = ProviderSchedule(
        provider_id=provider_id,
        schedule_type='block',
        specific_date=datetime.strptime(data['date'], '%Y-%m-%d').date(),
        start_time=datetime.strptime(data['start_time'], '%H:%M').time(),
        end_time=datetime.strptime(data['end_time'], '%H:%M').time(),
        is_active=True
    )
    
    db.session.add(block)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'block': block.to_dict()
    }), 201


# ============================================================
# APPOINTMENT TYPES
# ============================================================

@scheduling_bp.route('/appointment-types', methods=['GET'])
@login_required
def get_appointment_types():
    """Get all appointment types"""
    active_only = request.args.get('active_only', 'true').lower() == 'true'
    
    query = AppointmentType.query
    if active_only:
        query = query.filter_by(is_active=True)
    
    types = query.order_by(AppointmentType.name).all()
    
    return jsonify({
        'success': True,
        'appointment_types': [t.to_dict() for t in types]
    })


@scheduling_bp.route('/appointment-types', methods=['POST'])
@login_required
@permission_required('admin.scheduling')
def create_appointment_type():
    """Create a new appointment type"""
    data = request.get_json()
    
    apt_type = AppointmentType(
        name=data['name'],
        code=data.get('code'),
        description=data.get('description'),
        default_duration_minutes=data.get('default_duration_minutes', 30),
        min_duration_minutes=data.get('min_duration_minutes', 15),
        max_duration_minutes=data.get('max_duration_minutes', 120),
        color=data.get('color', '#0066cc'),
        is_telehealth=data.get('is_telehealth', False),
        requires_authorization=data.get('requires_authorization', False),
        allow_self_schedule=data.get('allow_self_schedule', False),
        allow_waitlist=data.get('allow_waitlist', True)
    )
    
    db.session.add(apt_type)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'appointment_type': apt_type.to_dict()
    }), 201


# ============================================================
# ROOMS & RESOURCES
# ============================================================

@scheduling_bp.route('/rooms', methods=['GET'])
@login_required
@permission_required('schedules.view')
def get_rooms():
    """Get all rooms"""
    department_id = request.args.get('department_id', type=int)
    facility_id = request.args.get('facility_id', type=int)
    
    query = Room.query.filter_by(is_active=True)
    
    if department_id:
        query = query.filter_by(department_id=department_id)
    if facility_id:
        query = query.filter_by(facility_id=facility_id)
    
    rooms = query.all()
    
    return jsonify({
        'success': True,
        'rooms': [r.to_dict() for r in rooms]
    })


@scheduling_bp.route('/rooms/<int:room_id>/availability', methods=['GET'])
@login_required
@permission_required('schedules.view')
def get_room_availability(room_id):
    """Get room availability for a date"""
    date_str = request.args.get('date')
    if not date_str:
        return jsonify({'success': False, 'error': 'date parameter required'}), 400
    
    check_date = datetime.strptime(date_str, '%Y-%m-%d').date()
    
    # Get all appointments for this room on this date
    appointments = Appointment.query.filter(
        Appointment.room_id == room_id,
        Appointment.scheduled_date == check_date,
        Appointment.status.notin_(['cancelled'])
    ).order_by(Appointment.scheduled_time).all()
    
    return jsonify({
        'success': True,
        'room_id': room_id,
        'date': check_date.isoformat(),
        'bookings': [{
            'appointment_id': apt.id,
            'start_time': apt.scheduled_time.isoformat(),
            'end_time': apt.end_datetime.time().isoformat(),
            'patient_id': apt.patient_id
        } for apt in appointments]
    })


# ============================================================
# WAITLIST
# ============================================================

@scheduling_bp.route('/waitlist', methods=['GET'])
@login_required
@permission_required('appointments.view')
def get_waitlist():
    """Get waitlist entries"""
    provider_id = request.args.get('provider_id', type=int)
    department_id = request.args.get('department_id', type=int)
    
    query = Waitlist.query.filter_by(status='active')
    
    if provider_id:
        query = query.filter_by(provider_id=provider_id)
    if department_id:
        query = query.filter_by(department_id=department_id)
    
    query = query.order_by(Waitlist.priority, Waitlist.added_at)
    
    entries = query.all()
    
    return jsonify({
        'success': True,
        'waitlist': [e.to_dict() for e in entries]
    })


@scheduling_bp.route('/waitlist', methods=['POST'])
@login_required
@permission_required('appointments.create')
def add_to_waitlist():
    """Add patient to waitlist"""
    data = request.get_json()
    
    entry = Waitlist(
        patient_id=data['patient_id'],
        provider_id=data.get('provider_id'),
        appointment_type_id=data.get('appointment_type_id'),
        facility_id=data.get('facility_id'),
        department_id=data.get('department_id'),
        preferred_date_start=datetime.strptime(data['preferred_date_start'], '%Y-%m-%d').date() if data.get('preferred_date_start') else None,
        preferred_date_end=datetime.strptime(data['preferred_date_end'], '%Y-%m-%d').date() if data.get('preferred_date_end') else None,
        preferred_time_start=datetime.strptime(data['preferred_time_start'], '%H:%M').time() if data.get('preferred_time_start') else None,
        preferred_time_end=datetime.strptime(data['preferred_time_end'], '%H:%M').time() if data.get('preferred_time_end') else None,
        preferred_days=data.get('preferred_days'),
        priority=data.get('priority', 5),
        is_urgent=data.get('is_urgent', False),
        reason_for_visit=data.get('reason_for_visit'),
        notes=data.get('notes'),
        contact_phone=data.get('contact_phone'),
        contact_email=data.get('contact_email'),
        contact_method=data.get('contact_method', 'phone'),
        added_by=g.current_user.id
    )
    
    db.session.add(entry)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'waitlist_entry': entry.to_dict()
    }), 201


@scheduling_bp.route('/waitlist/<int:entry_id>/schedule', methods=['POST'])
@login_required
@permission_required('appointments.create')
def schedule_from_waitlist(entry_id):
    """Schedule an appointment from waitlist entry"""
    entry = Waitlist.query.get_or_404(entry_id)
    data = request.get_json()
    
    # Create the appointment
    appointment = Appointment(
        patient_id=entry.patient_id,
        provider_id=data.get('provider_id', entry.provider_id),
        facility_id=data.get('facility_id', entry.facility_id),
        department_id=data.get('department_id', entry.department_id),
        appointment_type_id=data.get('appointment_type_id', entry.appointment_type_id),
        scheduled_date=datetime.strptime(data['scheduled_date'], '%Y-%m-%d').date(),
        scheduled_time=datetime.strptime(data['scheduled_time'], '%H:%M').time(),
        duration_minutes=data.get('duration_minutes', 30),
        reason_for_visit=entry.reason_for_visit,
        created_by=g.current_user.id
    )
    
    db.session.add(appointment)
    
    # Update waitlist entry
    entry.status = 'scheduled'
    entry.scheduled_appointment_id = appointment.id
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'appointment': appointment.to_dict(),
        'waitlist_entry': entry.to_dict()
    }), 201


# ============================================================
# CALENDAR VIEW
# ============================================================

@scheduling_bp.route('/calendar', methods=['GET'])
@login_required
@permission_required('appointments.view')
def get_calendar_view():
    """Get calendar view data for scheduling"""
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')
    view_type = request.args.get('view', 'day')  # day, week, month
    provider_ids = request.args.getlist('provider_ids', type=int)
    department_id = request.args.get('department_id', type=int)
    
    if not start_date:
        start_date = date.today().isoformat()
    if not end_date:
        if view_type == 'day':
            end_date = start_date
        elif view_type == 'week':
            end_date = (datetime.strptime(start_date, '%Y-%m-%d').date() + timedelta(days=6)).isoformat()
        else:  # month
            end_date = (datetime.strptime(start_date, '%Y-%m-%d').date() + timedelta(days=30)).isoformat()
    
    start = datetime.strptime(start_date, '%Y-%m-%d').date()
    end = datetime.strptime(end_date, '%Y-%m-%d').date()
    
    query = Appointment.query.filter(
        Appointment.scheduled_date >= start,
        Appointment.scheduled_date <= end
    )
    
    if provider_ids:
        query = query.filter(Appointment.provider_id.in_(provider_ids))
    if department_id:
        query = query.filter(Appointment.department_id == department_id)
    
    appointments = query.order_by(Appointment.scheduled_date, Appointment.scheduled_time).all()
    
    # Group by date
    calendar_data = {}
    for apt in appointments:
        date_key = apt.scheduled_date.isoformat()
        if date_key not in calendar_data:
            calendar_data[date_key] = []
        calendar_data[date_key].append(apt.to_dict())
    
    return jsonify({
        'success': True,
        'start_date': start_date,
        'end_date': end_date,
        'view_type': view_type,
        'calendar': calendar_data
    })


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def check_scheduling_conflicts(provider_id, room_id, scheduled_date, scheduled_time, duration, exclude_appointment_id=None):
    """Check for scheduling conflicts"""
    conflicts = []
    
    if isinstance(scheduled_date, str):
        scheduled_date = datetime.strptime(scheduled_date, '%Y-%m-%d').date()
    if isinstance(scheduled_time, str):
        scheduled_time = datetime.strptime(scheduled_time, '%H:%M').time()
    
    start_dt = datetime.combine(scheduled_date, scheduled_time)
    end_dt = start_dt + timedelta(minutes=duration)
    
    # Check provider conflicts
    if provider_id:
        query = Appointment.query.filter(
            Appointment.provider_id == provider_id,
            Appointment.scheduled_date == scheduled_date,
            Appointment.status.notin_(['cancelled'])
        )
        
        if exclude_appointment_id:
            query = query.filter(Appointment.id != exclude_appointment_id)
        
        for apt in query.all():
            apt_start = apt.scheduled_datetime
            apt_end = apt.end_datetime
            
            if (start_dt < apt_end and end_dt > apt_start):
                conflicts.append({
                    'type': 'provider',
                    'appointment_id': apt.id,
                    'scheduled_time': apt.scheduled_time.isoformat()
                })
    
    # Check room conflicts
    if room_id:
        query = Appointment.query.filter(
            Appointment.room_id == room_id,
            Appointment.scheduled_date == scheduled_date,
            Appointment.status.notin_(['cancelled'])
        )
        
        if exclude_appointment_id:
            query = query.filter(Appointment.id != exclude_appointment_id)
        
        for apt in query.all():
            apt_start = apt.scheduled_datetime
            apt_end = apt.end_datetime
            
            if (start_dt < apt_end and end_dt > apt_start):
                conflicts.append({
                    'type': 'room',
                    'appointment_id': apt.id,
                    'scheduled_time': apt.scheduled_time.isoformat()
                })
    
    return conflicts


def get_available_slots_for_day(provider_id, check_date, slot_duration):
    """Get available time slots for a provider on a specific day"""
    day_of_week = check_date.weekday()
    
    # Get provider's schedule for this day
    schedules = ProviderSchedule.query.filter(
        ProviderSchedule.provider_id == provider_id,
        ProviderSchedule.is_active == True,
        ProviderSchedule.schedule_type == 'regular',
        ProviderSchedule.day_of_week == day_of_week
    ).all()
    
    # Check for date-specific overrides
    overrides = ProviderSchedule.query.filter(
        ProviderSchedule.provider_id == provider_id,
        ProviderSchedule.is_active == True,
        ProviderSchedule.schedule_type == 'override',
        ProviderSchedule.specific_date == check_date
    ).all()
    
    # Check for blocks
    blocks = ProviderSchedule.query.filter(
        ProviderSchedule.provider_id == provider_id,
        ProviderSchedule.is_active == True,
        ProviderSchedule.schedule_type == 'block',
        ProviderSchedule.specific_date == check_date
    ).all()
    
    # Get existing appointments
    existing = Appointment.query.filter(
        Appointment.provider_id == provider_id,
        Appointment.scheduled_date == check_date,
        Appointment.status.notin_(['cancelled'])
    ).all()
    
    available_slots = []
    
    # Use overrides if present, otherwise use regular schedule
    working_schedules = overrides if overrides else schedules
    
    for schedule in working_schedules:
        current_time = datetime.combine(check_date, schedule.start_time)
        end_time = datetime.combine(check_date, schedule.end_time)
        
        while current_time + timedelta(minutes=slot_duration) <= end_time:
            slot_end = current_time + timedelta(minutes=slot_duration)
            
            # Check if slot is blocked
            is_blocked = False
            for block in blocks:
                block_start = datetime.combine(check_date, block.start_time)
                block_end = datetime.combine(check_date, block.end_time)
                if current_time < block_end and slot_end > block_start:
                    is_blocked = True
                    break
            
            # Check if slot is booked
            is_booked = False
            for apt in existing:
                apt_start = apt.scheduled_datetime
                apt_end = apt.end_datetime
                if current_time < apt_end and slot_end > apt_start:
                    is_booked = True
                    break
            
            if not is_blocked and not is_booked:
                available_slots.append(current_time.time().isoformat())
            
            current_time += timedelta(minutes=slot_duration)
    
    return available_slots


def create_recurring_appointments(parent_appointment, pattern_data):
    """Create recurring appointment instances"""
    pattern = RecurrencePattern(
        frequency=pattern_data['frequency'],
        interval=pattern_data.get('interval', 1),
        days_of_week=pattern_data.get('days_of_week'),
        day_of_month=pattern_data.get('day_of_month'),
        week_of_month=pattern_data.get('week_of_month'),
        end_type=pattern_data.get('end_type', 'count'),
        end_date=datetime.strptime(pattern_data['end_date'], '%Y-%m-%d').date() if pattern_data.get('end_date') else None,
        occurrence_count=pattern_data.get('occurrence_count', 4)
    )
    
    db.session.add(pattern)
    parent_appointment.recurrence_pattern_id = pattern.id
    
    # Generate recurring instances
    current_date = parent_appointment.scheduled_date
    count = 0
    max_count = pattern.occurrence_count or 52  # Max 1 year of weekly
    
    while count < max_count:
        if pattern.frequency == 'daily':
            current_date += timedelta(days=pattern.interval)
        elif pattern.frequency == 'weekly':
            current_date += timedelta(weeks=pattern.interval)
        elif pattern.frequency == 'biweekly':
            current_date += timedelta(weeks=2)
        elif pattern.frequency == 'monthly':
            # Move to next month
            month = current_date.month + pattern.interval
            year = current_date.year + (month - 1) // 12
            month = ((month - 1) % 12) + 1
            current_date = current_date.replace(year=year, month=month)
        
        # Check end conditions
        if pattern.end_type == 'date' and pattern.end_date and current_date > pattern.end_date:
            break
        
        # Create instance
        instance = Appointment(
            patient_id=parent_appointment.patient_id,
            provider_id=parent_appointment.provider_id,
            resource_id=parent_appointment.resource_id,
            facility_id=parent_appointment.facility_id,
            department_id=parent_appointment.department_id,
            room_id=parent_appointment.room_id,
            appointment_type_id=parent_appointment.appointment_type_id,
            scheduled_date=current_date,
            scheduled_time=parent_appointment.scheduled_time,
            duration_minutes=parent_appointment.duration_minutes,
            reason_for_visit=parent_appointment.reason_for_visit,
            is_recurring=True,
            recurrence_pattern_id=pattern.id,
            parent_appointment_id=parent_appointment.id,
            created_by=parent_appointment.created_by
        )
        
        db.session.add(instance)
        count += 1
