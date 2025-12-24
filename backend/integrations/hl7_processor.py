"""
HL7 v2.x Message Processing
ADT, ORM, ORU message parsing and generation
"""
from datetime import datetime
import re


class HL7Message:
    """Parse and generate HL7 v2.x messages"""
    
    SEGMENT_SEPARATOR = '\r'
    FIELD_SEPARATOR = '|'
    COMPONENT_SEPARATOR = '^'
    REPETITION_SEPARATOR = '~'
    ESCAPE_CHARACTER = '\\'
    SUBCOMPONENT_SEPARATOR = '&'
    
    def __init__(self, message_string=None):
        self.segments = []
        self.message_type = None
        self.message_control_id = None
        self.sending_application = None
        self.sending_facility = None
        self.receiving_application = None
        self.receiving_facility = None
        self.timestamp = None
        
        if message_string:
            self.parse(message_string)
    
    def parse(self, message_string):
        """Parse an HL7 message string"""
        # Normalize line endings
        message_string = message_string.replace('\r\n', '\r').replace('\n', '\r')
        
        # Split into segments
        segment_strings = message_string.strip().split(self.SEGMENT_SEPARATOR)
        
        for segment_string in segment_strings:
            if not segment_string.strip():
                continue
            
            segment = HL7Segment(segment_string)
            self.segments.append(segment)
        
        # Extract header info
        if self.segments and self.segments[0].name == 'MSH':
            msh = self.segments[0]
            self.sending_application = msh.get_field(3)
            self.sending_facility = msh.get_field(4)
            self.receiving_application = msh.get_field(5)
            self.receiving_facility = msh.get_field(6)
            self.timestamp = msh.get_field(7)
            self.message_type = msh.get_field(9)
            self.message_control_id = msh.get_field(10)
    
    def get_segment(self, name, index=0):
        """Get a segment by name"""
        matches = [s for s in self.segments if s.name == name]
        if index < len(matches):
            return matches[index]
        return None
    
    def get_all_segments(self, name):
        """Get all segments with a given name"""
        return [s for s in self.segments if s.name == name]
    
    def add_segment(self, segment):
        """Add a segment to the message"""
        self.segments.append(segment)
    
    def to_string(self):
        """Convert message to HL7 string"""
        return self.SEGMENT_SEPARATOR.join(s.to_string() for s in self.segments) + self.SEGMENT_SEPARATOR
    
    def create_ack(self, ack_code='AA', error_message=None):
        """Create an ACK message for this message"""
        ack = HL7Message()
        
        # MSH segment
        msh = HL7Segment('MSH')
        msh.set_field(1, self.FIELD_SEPARATOR)
        msh.set_field(2, f'{self.COMPONENT_SEPARATOR}{self.REPETITION_SEPARATOR}{self.ESCAPE_CHARACTER}{self.SUBCOMPONENT_SEPARATOR}')
        msh.set_field(3, self.receiving_application or 'EPIC_EHR')
        msh.set_field(4, self.receiving_facility or 'FACILITY')
        msh.set_field(5, self.sending_application or '')
        msh.set_field(6, self.sending_facility or '')
        msh.set_field(7, datetime.now().strftime('%Y%m%d%H%M%S'))
        msh.set_field(9, 'ACK')
        msh.set_field(10, f'ACK{datetime.now().strftime("%Y%m%d%H%M%S")}')
        msh.set_field(11, 'P')  # Processing ID
        msh.set_field(12, '2.5.1')  # Version
        ack.add_segment(msh)
        
        # MSA segment
        msa = HL7Segment('MSA')
        msa.set_field(1, ack_code)  # AA=Application Accept, AE=Application Error, AR=Application Reject
        msa.set_field(2, self.message_control_id or '')
        if error_message:
            msa.set_field(3, error_message)
        ack.add_segment(msa)
        
        return ack


class HL7Segment:
    """Represents a single HL7 segment"""
    
    def __init__(self, segment_string_or_name):
        self.name = ''
        self.fields = []
        
        if '|' in segment_string_or_name:
            self.parse(segment_string_or_name)
        else:
            self.name = segment_string_or_name
    
    def parse(self, segment_string):
        """Parse a segment string"""
        if segment_string.startswith('MSH'):
            # MSH segment is special - field separator is at position 3
            self.name = 'MSH'
            self.fields = ['', '|']  # Fields 0 and 1
            self.fields.extend(segment_string[4:].split('|'))
        else:
            parts = segment_string.split('|')
            self.name = parts[0]
            self.fields = [''] + parts[1:]  # Field 0 is empty, fields start at 1
    
    def get_field(self, index, component=None, subcomponent=None):
        """Get a field value, optionally with component/subcomponent"""
        if index >= len(self.fields):
            return ''
        
        value = self.fields[index]
        
        if component is not None:
            components = value.split('^')
            if component < len(components):
                value = components[component]
            else:
                return ''
        
        if subcomponent is not None:
            subcomponents = value.split('&')
            if subcomponent < len(subcomponents):
                value = subcomponents[subcomponent]
            else:
                return ''
        
        return value
    
    def set_field(self, index, value):
        """Set a field value"""
        while len(self.fields) <= index:
            self.fields.append('')
        self.fields[index] = value
    
    def to_string(self):
        """Convert segment to string"""
        if self.name == 'MSH':
            return 'MSH' + '|'.join(self.fields[1:])
        return self.name + '|' + '|'.join(self.fields[1:])


class ADTProcessor:
    """Process ADT (Admit, Discharge, Transfer) messages"""
    
    ADT_TYPES = {
        'A01': 'admit',
        'A02': 'transfer',
        'A03': 'discharge',
        'A04': 'register',
        'A05': 'preadmit',
        'A08': 'update_patient',
        'A11': 'cancel_admit',
        'A12': 'cancel_transfer',
        'A13': 'cancel_discharge',
        'A28': 'add_person',
        'A31': 'update_person',
        'A40': 'merge_patient'
    }
    
    @classmethod
    def process(cls, message):
        """Process an ADT message and return action data"""
        msg_type = message.message_type
        if '^' in msg_type:
            trigger_event = msg_type.split('^')[1]
        else:
            trigger_event = msg_type.replace('ADT_', '')
        
        action = cls.ADT_TYPES.get(trigger_event, 'unknown')
        
        # Extract patient data from PID segment
        pid = message.get_segment('PID')
        if not pid:
            raise ValueError('PID segment not found in ADT message')
        
        patient_data = cls._parse_pid(pid)
        
        # Extract visit data from PV1 segment
        pv1 = message.get_segment('PV1')
        visit_data = cls._parse_pv1(pv1) if pv1 else {}
        
        # Extract next of kin from NK1 segments
        nk1_segments = message.get_all_segments('NK1')
        contacts = [cls._parse_nk1(nk1) for nk1 in nk1_segments]
        
        # Extract insurance from IN1 segments
        in1_segments = message.get_all_segments('IN1')
        insurance = [cls._parse_in1(in1) for in1 in in1_segments]
        
        return {
            'action': action,
            'trigger_event': trigger_event,
            'patient': patient_data,
            'visit': visit_data,
            'contacts': contacts,
            'insurance': insurance,
            'message_control_id': message.message_control_id,
            'timestamp': message.timestamp
        }
    
    @classmethod
    def _parse_pid(cls, pid):
        """Parse PID segment"""
        return {
            'patient_id': pid.get_field(3, 0),  # Internal ID
            'external_id': pid.get_field(2, 0),
            'mrn': pid.get_field(3, 0),
            'last_name': pid.get_field(5, 0),
            'first_name': pid.get_field(5, 1),
            'middle_name': pid.get_field(5, 2),
            'suffix': pid.get_field(5, 4),
            'date_of_birth': cls._parse_date(pid.get_field(7)),
            'gender': pid.get_field(8),
            'race': pid.get_field(10),
            'address_line1': pid.get_field(11, 0),
            'address_line2': pid.get_field(11, 1),
            'city': pid.get_field(11, 2),
            'state': pid.get_field(11, 3),
            'zip_code': pid.get_field(11, 4),
            'country': pid.get_field(11, 5),
            'phone_home': pid.get_field(13),
            'phone_work': pid.get_field(14),
            'language': pid.get_field(15),
            'marital_status': pid.get_field(16),
            'religion': pid.get_field(17),
            'ssn': pid.get_field(19),
            'drivers_license': pid.get_field(20),
            'ethnicity': pid.get_field(22),
            'death_indicator': pid.get_field(30) == 'Y'
        }
    
    @classmethod
    def _parse_pv1(cls, pv1):
        """Parse PV1 (Patient Visit) segment"""
        return {
            'patient_class': pv1.get_field(2),  # I=Inpatient, O=Outpatient, E=Emergency
            'location': pv1.get_field(3, 0),
            'room': pv1.get_field(3, 1),
            'bed': pv1.get_field(3, 2),
            'facility': pv1.get_field(3, 3),
            'admission_type': pv1.get_field(4),
            'attending_doctor_id': pv1.get_field(7, 0),
            'attending_doctor_name': f'{pv1.get_field(7, 2)} {pv1.get_field(7, 1)}',
            'referring_doctor_id': pv1.get_field(8, 0),
            'consulting_doctor_id': pv1.get_field(9, 0),
            'hospital_service': pv1.get_field(10),
            'admit_source': pv1.get_field(14),
            'visit_number': pv1.get_field(19),
            'financial_class': pv1.get_field(20),
            'discharge_disposition': pv1.get_field(36),
            'admit_datetime': cls._parse_datetime(pv1.get_field(44)),
            'discharge_datetime': cls._parse_datetime(pv1.get_field(45))
        }
    
    @classmethod
    def _parse_nk1(cls, nk1):
        """Parse NK1 (Next of Kin) segment"""
        return {
            'set_id': nk1.get_field(1),
            'name': f'{nk1.get_field(2, 1)} {nk1.get_field(2, 0)}',
            'relationship': nk1.get_field(3, 1),
            'address': nk1.get_field(4, 0),
            'phone': nk1.get_field(5),
            'contact_role': nk1.get_field(7)
        }
    
    @classmethod
    def _parse_in1(cls, in1):
        """Parse IN1 (Insurance) segment"""
        return {
            'set_id': in1.get_field(1),
            'plan_id': in1.get_field(2),
            'company_name': in1.get_field(4),
            'company_address': in1.get_field(5, 0),
            'group_number': in1.get_field(8),
            'group_name': in1.get_field(9),
            'insured_id': in1.get_field(36),
            'policy_number': in1.get_field(36),
            'effective_date': cls._parse_date(in1.get_field(12)),
            'expiration_date': cls._parse_date(in1.get_field(13))
        }
    
    @staticmethod
    def _parse_date(date_string):
        """Parse HL7 date format (YYYYMMDD)"""
        if not date_string or len(date_string) < 8:
            return None
        try:
            return datetime.strptime(date_string[:8], '%Y%m%d').date()
        except ValueError:
            return None
    
    @staticmethod
    def _parse_datetime(datetime_string):
        """Parse HL7 datetime format (YYYYMMDDHHMMSS)"""
        if not datetime_string:
            return None
        try:
            if len(datetime_string) >= 14:
                return datetime.strptime(datetime_string[:14], '%Y%m%d%H%M%S')
            elif len(datetime_string) >= 12:
                return datetime.strptime(datetime_string[:12], '%Y%m%d%H%M')
            elif len(datetime_string) >= 8:
                return datetime.strptime(datetime_string[:8], '%Y%m%d')
        except ValueError:
            pass
        return None


class ORUProcessor:
    """Process ORU (Observation Result) messages - Lab results"""
    
    @classmethod
    def process(cls, message):
        """Process an ORU message"""
        # Get patient info
        pid = message.get_segment('PID')
        patient_data = ADTProcessor._parse_pid(pid) if pid else {}
        
        # Get visit info
        pv1 = message.get_segment('PV1')
        visit_data = ADTProcessor._parse_pv1(pv1) if pv1 else {}
        
        # Get order info from OBR segments
        obr_segments = message.get_all_segments('OBR')
        obx_segments = message.get_all_segments('OBX')
        
        results = []
        current_obr = None
        
        for segment in message.segments:
            if segment.name == 'OBR':
                current_obr = cls._parse_obr(segment)
            elif segment.name == 'OBX' and current_obr:
                obx_data = cls._parse_obx(segment)
                obx_data['order'] = current_obr
                results.append(obx_data)
        
        return {
            'patient': patient_data,
            'visit': visit_data,
            'results': results,
            'message_control_id': message.message_control_id,
            'timestamp': message.timestamp
        }
    
    @classmethod
    def _parse_obr(cls, obr):
        """Parse OBR (Observation Request) segment"""
        return {
            'set_id': obr.get_field(1),
            'placer_order_number': obr.get_field(2),
            'filler_order_number': obr.get_field(3),
            'universal_service_id': obr.get_field(4, 0),
            'universal_service_name': obr.get_field(4, 1),
            'priority': obr.get_field(5),
            'requested_datetime': ADTProcessor._parse_datetime(obr.get_field(6)),
            'observation_datetime': ADTProcessor._parse_datetime(obr.get_field(7)),
            'collector_id': obr.get_field(10),
            'specimen_source': obr.get_field(15),
            'ordering_provider_id': obr.get_field(16, 0),
            'ordering_provider_name': f'{obr.get_field(16, 2)} {obr.get_field(16, 1)}',
            'result_status': obr.get_field(25)
        }
    
    @classmethod
    def _parse_obx(cls, obx):
        """Parse OBX (Observation Result) segment"""
        return {
            'set_id': obx.get_field(1),
            'value_type': obx.get_field(2),
            'observation_id': obx.get_field(3, 0),
            'observation_name': obx.get_field(3, 1),
            'observation_sub_id': obx.get_field(4),
            'value': obx.get_field(5),
            'units': obx.get_field(6, 0),
            'reference_range': obx.get_field(7),
            'abnormal_flags': obx.get_field(8),
            'probability': obx.get_field(9),
            'observation_result_status': obx.get_field(11),
            'observation_datetime': ADTProcessor._parse_datetime(obx.get_field(14)),
            'producer_id': obx.get_field(15),
            'performing_organization': obx.get_field(23)
        }


class HL7MessageBuilder:
    """Build HL7 messages for outbound communication"""
    
    @staticmethod
    def build_adt_a01(patient_data, visit_data, sending_app='EPIC_EHR', sending_facility='FACILITY'):
        """Build an ADT A01 (Admit) message"""
        msg = HL7Message()
        
        # MSH segment
        msh = HL7Segment('MSH')
        msh.set_field(1, '|')
        msh.set_field(2, '^~\\&')
        msh.set_field(3, sending_app)
        msh.set_field(4, sending_facility)
        msh.set_field(5, '')  # Receiving app
        msh.set_field(6, '')  # Receiving facility
        msh.set_field(7, datetime.now().strftime('%Y%m%d%H%M%S'))
        msh.set_field(9, 'ADT^A01^ADT_A01')
        msh.set_field(10, f'MSG{datetime.now().strftime("%Y%m%d%H%M%S")}')
        msh.set_field(11, 'P')
        msh.set_field(12, '2.5.1')
        msg.add_segment(msh)
        
        # EVN segment
        evn = HL7Segment('EVN')
        evn.set_field(1, 'A01')
        evn.set_field(2, datetime.now().strftime('%Y%m%d%H%M%S'))
        msg.add_segment(evn)
        
        # PID segment
        pid = HL7Segment('PID')
        pid.set_field(1, '1')
        pid.set_field(3, f'{patient_data.get("mrn", "")}^^^MRN')
        pid.set_field(5, f'{patient_data.get("last_name", "")}^{patient_data.get("first_name", "")}^{patient_data.get("middle_name", "")}')
        if patient_data.get('date_of_birth'):
            dob = patient_data['date_of_birth']
            if isinstance(dob, str):
                pid.set_field(7, dob.replace('-', ''))
            else:
                pid.set_field(7, dob.strftime('%Y%m%d'))
        pid.set_field(8, patient_data.get('gender', 'U')[0].upper())
        if patient_data.get('address_line1'):
            addr = f'{patient_data.get("address_line1", "")}^^{patient_data.get("city", "")}^{patient_data.get("state", "")}^{patient_data.get("zip_code", "")}'
            pid.set_field(11, addr)
        if patient_data.get('phone'):
            pid.set_field(13, patient_data['phone'])
        msg.add_segment(pid)
        
        # PV1 segment
        pv1 = HL7Segment('PV1')
        pv1.set_field(1, '1')
        pv1.set_field(2, visit_data.get('patient_class', 'I')[0].upper())
        location = f'{visit_data.get("location", "")}^{visit_data.get("room", "")}^{visit_data.get("bed", "")}'
        pv1.set_field(3, location)
        pv1.set_field(4, visit_data.get('admission_type', ''))
        if visit_data.get('attending_doctor_id'):
            pv1.set_field(7, f'{visit_data["attending_doctor_id"]}^{visit_data.get("attending_doctor_name", "")}')
        pv1.set_field(19, visit_data.get('visit_number', ''))
        if visit_data.get('admit_datetime'):
            pv1.set_field(44, visit_data['admit_datetime'].strftime('%Y%m%d%H%M%S'))
        msg.add_segment(pv1)
        
        return msg
    
    @staticmethod
    def build_orm_o01(order_data, patient_data, sending_app='EPIC_EHR', sending_facility='FACILITY'):
        """Build an ORM O01 (Order) message"""
        msg = HL7Message()
        
        # MSH segment
        msh = HL7Segment('MSH')
        msh.set_field(1, '|')
        msh.set_field(2, '^~\\&')
        msh.set_field(3, sending_app)
        msh.set_field(4, sending_facility)
        msh.set_field(7, datetime.now().strftime('%Y%m%d%H%M%S'))
        msh.set_field(9, 'ORM^O01^ORM_O01')
        msh.set_field(10, f'ORM{datetime.now().strftime("%Y%m%d%H%M%S")}')
        msh.set_field(11, 'P')
        msh.set_field(12, '2.5.1')
        msg.add_segment(msh)
        
        # PID segment
        pid = HL7Segment('PID')
        pid.set_field(1, '1')
        pid.set_field(3, f'{patient_data.get("mrn", "")}^^^MRN')
        pid.set_field(5, f'{patient_data.get("last_name", "")}^{patient_data.get("first_name", "")}')
        msg.add_segment(pid)
        
        # ORC segment (Common Order)
        orc = HL7Segment('ORC')
        orc.set_field(1, 'NW')  # New Order
        orc.set_field(2, order_data.get('placer_order_number', ''))
        orc.set_field(5, 'SC')  # Scheduled
        orc.set_field(9, datetime.now().strftime('%Y%m%d%H%M%S'))
        if order_data.get('ordering_provider'):
            orc.set_field(12, f'{order_data["ordering_provider"].get("id", "")}^{order_data["ordering_provider"].get("name", "")}')
        msg.add_segment(orc)
        
        # OBR segment (Observation Request)
        obr = HL7Segment('OBR')
        obr.set_field(1, '1')
        obr.set_field(2, order_data.get('placer_order_number', ''))
        obr.set_field(4, f'{order_data.get("order_code", "")}^{order_data.get("order_name", "")}')
        obr.set_field(7, datetime.now().strftime('%Y%m%d%H%M%S'))
        msg.add_segment(obr)
        
        return msg
