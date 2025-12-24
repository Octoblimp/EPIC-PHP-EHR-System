"""
Non-Clinical Routes
Billing, Messaging, Documents, Reporting APIs
"""
from flask import Blueprint, request, jsonify, session, g, send_file
from datetime import datetime, date, timedelta
from models.non_clinical import (
    db, BillingAccount, Charge, Claim, Payment,
    MessageThread, Message, MessageRecipient, MessageAttachment,
    Document, DocumentTemplate, 
    ReportDefinition, ReportExecution, ScheduledReport,
    Task, Alert
)
from models.audit import AuditLog, PHIAccessLog
from routes.auth_routes import login_required, permission_required
import json
import uuid
import os

non_clinical_bp = Blueprint('non_clinical', __name__, url_prefix='/api')


# ============================================================
# BILLING
# ============================================================

@non_clinical_bp.route('/billing/accounts/<int:patient_id>', methods=['GET'])
@login_required
@permission_required('billing.view')
def get_billing_account(patient_id):
    """Get patient's billing account"""
    account = BillingAccount.query.filter_by(patient_id=patient_id).first()
    
    if not account:
        return jsonify({'success': False, 'error': 'Account not found'}), 404
    
    # Audit
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='VIEW',
        resource_type='BillingAccount',
        resource_id=account.id
    )
    
    return jsonify({
        'success': True,
        'account': account.to_dict()
    })


@non_clinical_bp.route('/billing/charges', methods=['GET'])
@login_required
@permission_required('billing.view')
def get_charges():
    """Get charges with filters"""
    patient_id = request.args.get('patient_id', type=int)
    account_id = request.args.get('account_id', type=int)
    status = request.args.get('status')
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')
    
    query = Charge.query
    
    if account_id:
        query = query.filter_by(billing_account_id=account_id)
    if status:
        query = query.filter_by(status=status)
    if start_date:
        query = query.filter(Charge.service_date >= datetime.strptime(start_date, '%Y-%m-%d').date())
    if end_date:
        query = query.filter(Charge.service_date <= datetime.strptime(end_date, '%Y-%m-%d').date())
    
    charges = query.order_by(Charge.service_date.desc()).all()
    
    return jsonify({
        'success': True,
        'charges': [c.to_dict() for c in charges]
    })


@non_clinical_bp.route('/billing/charges', methods=['POST'])
@login_required
@permission_required('billing.create')
def create_charge():
    """Create a new charge"""
    data = request.get_json()
    
    charge = Charge(
        billing_account_id=data['billing_account_id'],
        encounter_id=data.get('encounter_id'),
        service_date=datetime.strptime(data['service_date'], '%Y-%m-%d').date(),
        procedure_code=data['procedure_code'],
        procedure_description=data.get('procedure_description'),
        diagnosis_codes=json.dumps(data.get('diagnosis_codes', [])),
        units=data.get('units', 1),
        unit_charge=data['unit_charge'],
        total_charge=data['unit_charge'] * data.get('units', 1),
        modifier1=data.get('modifier1'),
        modifier2=data.get('modifier2'),
        modifier3=data.get('modifier3'),
        modifier4=data.get('modifier4'),
        place_of_service=data.get('place_of_service', '11'),
        rendering_provider_id=data.get('rendering_provider_id'),
        status='pending',
        posted_by=g.current_user.id,
        posted_at=datetime.utcnow()
    )
    
    db.session.add(charge)
    
    # Update account balance
    account = BillingAccount.query.get(data['billing_account_id'])
    if account:
        account.current_balance += charge.total_charge
    
    db.session.commit()
    
    # Audit
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='CREATE',
        resource_type='Charge',
        resource_id=charge.id,
        new_values=charge.to_dict()
    )
    
    return jsonify({
        'success': True,
        'charge': charge.to_dict()
    }), 201


@non_clinical_bp.route('/billing/claims', methods=['GET'])
@login_required
@permission_required('billing.view')
def get_claims():
    """Get claims with filters"""
    status = request.args.get('status')
    payer_id = request.args.get('payer_id')
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')
    
    query = Claim.query
    
    if status:
        query = query.filter_by(status=status)
    if payer_id:
        query = query.filter_by(payer_id=payer_id)
    if start_date:
        query = query.filter(Claim.submission_date >= datetime.strptime(start_date, '%Y-%m-%d').date())
    if end_date:
        query = query.filter(Claim.submission_date <= datetime.strptime(end_date, '%Y-%m-%d').date())
    
    claims = query.order_by(Claim.created_at.desc()).all()
    
    return jsonify({
        'success': True,
        'claims': [c.to_dict() for c in claims]
    })


@non_clinical_bp.route('/billing/claims', methods=['POST'])
@login_required
@permission_required('billing.submit_claims')
def create_claim():
    """Create and submit a claim"""
    data = request.get_json()
    
    claim = Claim(
        claim_number=f"CLM{datetime.now().strftime('%Y%m%d%H%M%S')}{uuid.uuid4().hex[:4].upper()}",
        billing_account_id=data['billing_account_id'],
        insurance_id=data['insurance_id'],
        payer_id=data.get('payer_id'),
        payer_name=data.get('payer_name'),
        service_date_from=datetime.strptime(data['service_date_from'], '%Y-%m-%d').date(),
        service_date_to=datetime.strptime(data['service_date_to'], '%Y-%m-%d').date(),
        total_charges=data['total_charges'],
        claim_type=data.get('claim_type', 'professional'),
        status='created',
        created_by=g.current_user.id
    )
    
    db.session.add(claim)
    db.session.commit()
    
    # Audit
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='CREATE',
        resource_type='Claim',
        resource_id=claim.id,
        new_values=claim.to_dict()
    )
    
    return jsonify({
        'success': True,
        'claim': claim.to_dict()
    }), 201


@non_clinical_bp.route('/billing/payments', methods=['POST'])
@login_required
@permission_required('billing.post_payments')
def post_payment():
    """Post a payment"""
    data = request.get_json()
    
    payment = Payment(
        payment_number=f"PMT{datetime.now().strftime('%Y%m%d%H%M%S')}{uuid.uuid4().hex[:4].upper()}",
        billing_account_id=data['billing_account_id'],
        payment_date=datetime.strptime(data['payment_date'], '%Y-%m-%d').date(),
        payment_amount=data['payment_amount'],
        payment_source=data['payment_source'],
        payer_type=data.get('payer_type'),
        payment_method=data['payment_method'],
        check_number=data.get('check_number'),
        reference_number=data.get('reference_number'),
        claim_id=data.get('claim_id'),
        posted_by=g.current_user.id
    )
    
    db.session.add(payment)
    
    # Update account balance
    account = BillingAccount.query.get(data['billing_account_id'])
    if account:
        account.current_balance -= payment.payment_amount
        if data['payment_source'] == 'insurance':
            account.insurance_balance -= payment.payment_amount
        else:
            account.patient_balance -= payment.payment_amount
    
    db.session.commit()
    
    # Audit
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='CREATE',
        resource_type='Payment',
        resource_id=payment.id,
        new_values=payment.to_dict()
    )
    
    return jsonify({
        'success': True,
        'payment': payment.to_dict()
    }), 201


# ============================================================
# MESSAGING
# ============================================================

@non_clinical_bp.route('/messages/threads', methods=['GET'])
@login_required
@permission_required('messages.view')
def get_message_threads():
    """Get message threads for current user"""
    status = request.args.get('status', 'open')
    
    # Get threads where user is a recipient
    threads = db.session.query(MessageThread).join(
        Message, MessageThread.id == Message.thread_id
    ).join(
        MessageRecipient, Message.id == MessageRecipient.message_id
    ).filter(
        MessageRecipient.recipient_id == g.current_user.id,
        MessageRecipient.is_deleted == False
    )
    
    if status != 'all':
        threads = threads.filter(MessageThread.status == status)
    
    threads = threads.distinct().order_by(MessageThread.last_message_at.desc()).all()
    
    return jsonify({
        'success': True,
        'threads': [t.to_dict() for t in threads]
    })


@non_clinical_bp.route('/messages/threads/<int:thread_id>', methods=['GET'])
@login_required
@permission_required('messages.view')
def get_thread_messages(thread_id):
    """Get all messages in a thread"""
    thread = MessageThread.query.get_or_404(thread_id)
    
    messages = Message.query.filter_by(
        thread_id=thread_id,
        is_draft=False
    ).order_by(Message.sent_at).all()
    
    # Mark as read for current user
    for msg in messages:
        recipient = MessageRecipient.query.filter_by(
            message_id=msg.id,
            recipient_id=g.current_user.id
        ).first()
        if recipient and not recipient.is_read:
            recipient.is_read = True
            recipient.read_at = datetime.utcnow()
    
    db.session.commit()
    
    # PHI audit if patient-related
    if thread.patient_id:
        PHIAccessLog.log_access(
            user_id=g.current_user.id,
            patient_id=thread.patient_id,
            resource_type='MessageThread',
            resource_id=thread_id,
            access_type='READ',
            reason='Viewing message thread'
        )
    
    return jsonify({
        'success': True,
        'thread': thread.to_dict(),
        'messages': [m.to_dict() for m in messages]
    })


@non_clinical_bp.route('/messages/threads', methods=['POST'])
@login_required
@permission_required('messages.send')
def create_thread():
    """Create a new message thread"""
    data = request.get_json()
    
    # Create thread
    thread = MessageThread(
        subject=data['subject'],
        thread_type=data.get('thread_type', 'general'),
        priority=data.get('priority', 'normal'),
        patient_id=data.get('patient_id'),
        created_by=g.current_user.id
    )
    
    db.session.add(thread)
    db.session.flush()
    
    # Create first message
    message = Message(
        thread_id=thread.id,
        sender_id=g.current_user.id,
        sender_type='staff',
        content=data['message'],
        content_type=data.get('content_type', 'text')
    )
    
    db.session.add(message)
    db.session.flush()
    
    # Add recipients
    for recipient_id in data.get('recipients', []):
        recipient = MessageRecipient(
            message_id=message.id,
            recipient_id=recipient_id,
            recipient_type='to'
        )
        db.session.add(recipient)
    
    for cc_id in data.get('cc', []):
        recipient = MessageRecipient(
            message_id=message.id,
            recipient_id=cc_id,
            recipient_type='cc'
        )
        db.session.add(recipient)
    
    db.session.commit()
    
    # Audit
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='CREATE',
        resource_type='MessageThread',
        resource_id=thread.id,
        new_values={'subject': thread.subject, 'recipients': data.get('recipients', [])}
    )
    
    return jsonify({
        'success': True,
        'thread': thread.to_dict(),
        'message': message.to_dict()
    }), 201


@non_clinical_bp.route('/messages/threads/<int:thread_id>/reply', methods=['POST'])
@login_required
@permission_required('messages.send')
def reply_to_thread(thread_id):
    """Reply to a message thread"""
    thread = MessageThread.query.get_or_404(thread_id)
    data = request.get_json()
    
    message = Message(
        thread_id=thread_id,
        sender_id=g.current_user.id,
        sender_type='staff',
        content=data['message'],
        content_type=data.get('content_type', 'text'),
        reply_to_id=data.get('reply_to_id')
    )
    
    db.session.add(message)
    db.session.flush()
    
    # Add all previous participants as recipients
    previous_messages = Message.query.filter_by(thread_id=thread_id).all()
    participants = set()
    
    for msg in previous_messages:
        participants.add(msg.sender_id)
        for r in MessageRecipient.query.filter_by(message_id=msg.id).all():
            participants.add(r.recipient_id)
    
    # Remove current user from recipients
    participants.discard(g.current_user.id)
    
    for participant_id in participants:
        recipient = MessageRecipient(
            message_id=message.id,
            recipient_id=participant_id,
            recipient_type='to'
        )
        db.session.add(recipient)
    
    # Update thread
    thread.last_message_at = datetime.utcnow()
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'message': message.to_dict()
    }), 201


@non_clinical_bp.route('/messages/inbox/count', methods=['GET'])
@login_required
def get_unread_count():
    """Get unread message count"""
    count = db.session.query(MessageRecipient).filter(
        MessageRecipient.recipient_id == g.current_user.id,
        MessageRecipient.is_read == False,
        MessageRecipient.is_deleted == False
    ).count()
    
    return jsonify({
        'success': True,
        'unread_count': count
    })


# ============================================================
# DOCUMENTS
# ============================================================

@non_clinical_bp.route('/documents', methods=['GET'])
@login_required
@permission_required('documents.view')
def get_documents():
    """Get documents with filters"""
    patient_id = request.args.get('patient_id', type=int)
    document_type = request.args.get('document_type')
    category = request.args.get('category')
    status = request.args.get('status', 'active')
    
    query = Document.query.filter_by(status=status)
    
    if patient_id:
        query = query.filter_by(patient_id=patient_id)
    if document_type:
        query = query.filter_by(document_type=document_type)
    if category:
        query = query.filter_by(category=category)
    
    documents = query.order_by(Document.created_at.desc()).all()
    
    return jsonify({
        'success': True,
        'documents': [d.to_dict() for d in documents]
    })


@non_clinical_bp.route('/documents/<int:document_id>', methods=['GET'])
@login_required
@permission_required('documents.view')
def get_document(document_id):
    """Get document metadata"""
    document = Document.query.get_or_404(document_id)
    
    # PHI audit
    if document.patient_id:
        PHIAccessLog.log_access(
            user_id=g.current_user.id,
            patient_id=document.patient_id,
            resource_type='Document',
            resource_id=document_id,
            access_type='READ',
            reason='Viewing document metadata'
        )
    
    return jsonify({
        'success': True,
        'document': document.to_dict()
    })


@non_clinical_bp.route('/documents/upload', methods=['POST'])
@login_required
@permission_required('documents.upload')
def upload_document():
    """Upload a document"""
    if 'file' not in request.files:
        return jsonify({'success': False, 'error': 'No file provided'}), 400
    
    file = request.files['file']
    data = request.form
    
    # Generate secure filename and path
    file_uuid = str(uuid.uuid4())
    file_ext = os.path.splitext(file.filename)[1]
    secure_filename = f"{file_uuid}{file_ext}"
    
    # In production, this would go to encrypted storage
    # file_path = save_encrypted_file(file, secure_filename)
    file_path = f"/secure/documents/{secure_filename}"
    
    document = Document(
        title=data.get('title', file.filename),
        description=data.get('description'),
        document_type=data.get('document_type', 'general'),
        category=data.get('category'),
        patient_id=data.get('patient_id', type=int),
        encounter_id=data.get('encounter_id', type=int),
        file_name=file.filename,
        file_type=file.content_type,
        file_size=0,  # Would calculate from actual file
        file_path=file_path,
        is_encrypted=True,
        source='upload',
        document_date=datetime.strptime(data['document_date'], '%Y-%m-%d').date() if data.get('document_date') else date.today(),
        created_by=g.current_user.id
    )
    
    db.session.add(document)
    db.session.commit()
    
    # Audit
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='UPLOAD',
        resource_type='Document',
        resource_id=document.id,
        new_values={'file_name': document.file_name, 'patient_id': document.patient_id}
    )
    
    return jsonify({
        'success': True,
        'document': document.to_dict()
    }), 201


@non_clinical_bp.route('/documents/<int:document_id>/sign', methods=['POST'])
@login_required
@permission_required('documents.sign')
def sign_document(document_id):
    """Electronically sign a document"""
    document = Document.query.get_or_404(document_id)
    
    if not document.requires_signature:
        return jsonify({'success': False, 'error': 'Document does not require signature'}), 400
    
    if document.signed_by:
        return jsonify({'success': False, 'error': 'Document already signed'}), 400
    
    document.signed_by = g.current_user.id
    document.signed_at = datetime.utcnow()
    document.review_status = 'signed'
    
    db.session.commit()
    
    # Audit
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='SIGN',
        resource_type='Document',
        resource_id=document_id
    )
    
    return jsonify({
        'success': True,
        'document': document.to_dict()
    })


@non_clinical_bp.route('/documents/templates', methods=['GET'])
@login_required
@permission_required('documents.view')
def get_document_templates():
    """Get document templates"""
    template_type = request.args.get('type')
    
    query = DocumentTemplate.query.filter_by(is_active=True)
    
    if template_type:
        query = query.filter_by(template_type=template_type)
    
    templates = query.all()
    
    return jsonify({
        'success': True,
        'templates': [t.to_dict() for t in templates]
    })


# ============================================================
# REPORTING
# ============================================================

@non_clinical_bp.route('/reports', methods=['GET'])
@login_required
@permission_required('reports.view')
def get_reports():
    """Get available reports"""
    report_type = request.args.get('type')
    category = request.args.get('category')
    
    query = ReportDefinition.query.filter_by(is_active=True)
    
    if report_type:
        query = query.filter_by(report_type=report_type)
    if category:
        query = query.filter_by(category=category)
    
    # Filter by permission
    reports = []
    for report in query.all():
        if not report.required_permission or g.current_user.has_permission(report.required_permission):
            reports.append(report)
    
    return jsonify({
        'success': True,
        'reports': [r.to_dict() for r in reports]
    })


@non_clinical_bp.route('/reports/<int:report_id>/execute', methods=['POST'])
@login_required
@permission_required('reports.execute')
def execute_report(report_id):
    """Execute a report"""
    report = ReportDefinition.query.get_or_404(report_id)
    data = request.get_json() or {}
    
    # Check specific permission
    if report.required_permission and not g.current_user.has_permission(report.required_permission):
        return jsonify({'success': False, 'error': 'Insufficient permissions'}), 403
    
    # Create execution record
    execution = ReportExecution(
        report_id=report_id,
        parameters_used=json.dumps(data.get('parameters', {})),
        output_format=data.get('format', report.default_format),
        status='queued',
        executed_by=g.current_user.id
    )
    
    db.session.add(execution)
    db.session.commit()
    
    # In production, this would queue the report for async execution
    # For now, simulate immediate execution
    execution.status = 'running'
    execution.started_at = datetime.utcnow()
    db.session.commit()
    
    # Audit
    AuditLog.log_action(
        user_id=g.current_user.id,
        action='EXECUTE',
        resource_type='Report',
        resource_id=report_id,
        new_values={'parameters': data.get('parameters', {})}
    )
    
    return jsonify({
        'success': True,
        'execution': execution.to_dict()
    }), 202


@non_clinical_bp.route('/reports/executions/<int:execution_id>', methods=['GET'])
@login_required
@permission_required('reports.view')
def get_report_execution(execution_id):
    """Get report execution status"""
    execution = ReportExecution.query.get_or_404(execution_id)
    
    return jsonify({
        'success': True,
        'execution': execution.to_dict()
    })


# ============================================================
# TASKS
# ============================================================

@non_clinical_bp.route('/tasks', methods=['GET'])
@login_required
@permission_required('tasks.view')
def get_tasks():
    """Get tasks for current user"""
    status = request.args.get('status', 'open')
    patient_id = request.args.get('patient_id', type=int)
    
    query = Task.query.filter(
        (Task.assigned_to == g.current_user.id) |
        (Task.assigned_to_department == g.current_user.department_id)
    )
    
    if status != 'all':
        query = query.filter_by(status=status)
    if patient_id:
        query = query.filter_by(patient_id=patient_id)
    
    tasks = query.order_by(Task.due_date, Task.priority.desc()).all()
    
    return jsonify({
        'success': True,
        'tasks': [t.to_dict() for t in tasks]
    })


@non_clinical_bp.route('/tasks', methods=['POST'])
@login_required
@permission_required('tasks.create')
def create_task():
    """Create a new task"""
    data = request.get_json()
    
    task = Task(
        title=data['title'],
        description=data.get('description'),
        task_type=data.get('task_type'),
        priority=data.get('priority', 'normal'),
        patient_id=data.get('patient_id'),
        encounter_id=data.get('encounter_id'),
        assigned_to=data.get('assigned_to'),
        assigned_to_role=data.get('assigned_to_role'),
        assigned_to_department=data.get('assigned_to_department'),
        due_date=datetime.strptime(data['due_date'], '%Y-%m-%d').date() if data.get('due_date') else None,
        due_time=datetime.strptime(data['due_time'], '%H:%M').time() if data.get('due_time') else None,
        created_by=g.current_user.id
    )
    
    db.session.add(task)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'task': task.to_dict()
    }), 201


@non_clinical_bp.route('/tasks/<int:task_id>/complete', methods=['POST'])
@login_required
@permission_required('tasks.complete')
def complete_task(task_id):
    """Complete a task"""
    task = Task.query.get_or_404(task_id)
    data = request.get_json() or {}
    
    task.status = 'completed'
    task.completed_at = datetime.utcnow()
    task.completed_by = g.current_user.id
    task.completion_notes = data.get('notes')
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'task': task.to_dict()
    })


# ============================================================
# ALERTS
# ============================================================

@non_clinical_bp.route('/alerts', methods=['GET'])
@login_required
def get_alerts():
    """Get alerts for current user"""
    include_read = request.args.get('include_read', 'false').lower() == 'true'
    
    query = Alert.query.filter(
        (Alert.target_user_id == g.current_user.id) |
        (Alert.target_department == g.current_user.department_id),
        Alert.is_dismissed == False,
        (Alert.expires_at == None) | (Alert.expires_at > datetime.utcnow())
    )
    
    if not include_read:
        query = query.filter(Alert.is_read == False)
    
    alerts = query.order_by(Alert.severity.desc(), Alert.created_at.desc()).all()
    
    return jsonify({
        'success': True,
        'alerts': [a.to_dict() for a in alerts]
    })


@non_clinical_bp.route('/alerts/<int:alert_id>/read', methods=['POST'])
@login_required
def mark_alert_read(alert_id):
    """Mark an alert as read"""
    alert = Alert.query.get_or_404(alert_id)
    
    alert.is_read = True
    alert.read_at = datetime.utcnow()
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'alert': alert.to_dict()
    })


@non_clinical_bp.route('/alerts/<int:alert_id>/dismiss', methods=['POST'])
@login_required
def dismiss_alert(alert_id):
    """Dismiss an alert"""
    alert = Alert.query.get_or_404(alert_id)
    
    alert.is_dismissed = True
    alert.dismissed_at = datetime.utcnow()
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'alert': alert.to_dict()
    })
