# Epic EHR System - PHP Frontend with Python Backend

A comprehensive web-based Electronic Health Record (EHR) system inspired by Epic's Hyperspace interface. This project features a PHP/HTML/CSS/JavaScript frontend that communicates with a Python Flask backend API, designed for production healthcare environments with full HIPAA compliance features.

![Epic EHR Demo](docs/screenshot.png)

## Features

### Clinical Modules
- **Patient Demographics** - Complete patient information header with allergies, identifiers, and encounter details
- **Flowsheets** - Time-based documentation with multiple flowsheet groups:
  - Pastoral Services
  - Post Partum Hemorrhage
  - Intake/Output
  - Nursing Assessment
  - Pediatrics/Growth Charts
  - Oncology Treatment
  - Perinatal/Labor & Delivery
  - Infection Control
- **Medications** - Active medication list with categorization (scheduled, PRN, continuous)
- **MAR (Medication Administration Record)** - Full medication administration tracking
- **Orders** - Order entry, acknowledgment, and tracking with order sets
- **Vital Signs** - Recording and trending of vital signs with detailed documentation
- **Lab Results** - Laboratory result viewing with flagging and critical value alerts
- **Clinical Notes** - Note creation, signing, co-signing, and SmartPhrase templates
- **Chart Review** - Document management and historical review
- **Care Plans** - Problem-based care planning with goals and interventions

### Administrative Modules
- **Facility Dashboard** - Real-time census, department status, alerts, and quick actions
- **User Management** - Role-based access control with granular permissions
- **Scheduling** - Appointment scheduling and provider management
- **Billing** - Charge capture and billing account management
- **Reports** - Configurable clinical and operational reports

### Technical Features
- RESTful API architecture
- SQLite database (easily upgradeable to PostgreSQL/MySQL)
- FHIR R4 compliant endpoints
- HL7 v2.5.1 message processing (ADT, ORM, ORU, MDM)
- HIPAA-compliant audit logging
- Session timeout (15 minutes) for security
- Role-based permissions with break-the-glass support
- Real-time data updates with auto-refresh
- Responsive Epic-style UI

## Project Structure

```
EPIC-PHP-EHR-System/
├── backend/                    # Python Flask API Server
│   ├── app.py                  # Main application entry point
│   ├── config.py               # Configuration settings
│   ├── requirements.txt        # Python dependencies
│   ├── models/                 # Database models
│   │   ├── __init__.py         # Models package init
│   │   ├── patient.py          # Patient & Allergy models
│   │   ├── encounter.py        # Encounter model
│   │   ├── medication.py       # Medication & MAR models
│   │   ├── order.py            # Order & OrderSet models
│   │   ├── vital.py            # Vital signs model
│   │   ├── flowsheet.py        # Flowsheet models
│   │   ├── lab.py              # Lab result model
│   │   ├── note.py             # Clinical notes & SmartPhrase models
│   │   ├── user.py             # User/Provider model
│   │   ├── auth.py             # Authentication, roles, permissions, facilities
│   │   ├── role.py             # Role definitions
│   │   ├── scheduling.py       # Appointments, providers, rooms
│   │   ├── audit.py            # Audit logging & PHI access tracking
│   │   ├── non_clinical.py     # Billing, documents, alerts, tasks, reports
│   │   └── theme.py            # UI theming
│   ├── routes/                 # API route handlers
│   │   ├── __init__.py         # Routes package init
│   │   ├── patient_routes.py
│   │   ├── medication_routes.py
│   │   ├── order_routes.py
│   │   ├── vital_routes.py
│   │   ├── flowsheet_routes.py
│   │   ├── lab_routes.py
│   │   ├── note_routes.py
│   │   ├── fhir_routes.py      # FHIR R4 endpoints
│   │   └── non_clinical_routes.py  # Alerts, tasks, messages
│   └── integrations/
│       └── hl7_processor.py    # HL7 message parsing and building
│
├── frontend/                   # PHP Web Frontend
│   ├── index.php               # Facility dashboard (home)
│   ├── login.php               # Authentication
│   ├── logout.php              # Session termination
│   ├── home.php                # User home/module launcher
│   ├── patients.php            # Patient list/search
│   ├── patient-lists.php       # Custom patient lists
│   ├── notes.php               # Clinical notes with SmartText
│   ├── admin.php               # System administration
│   ├── .htaccess               # Apache URL rewriting
│   ├── apache.conf             # Apache configuration
│   ├── includes/
│   │   ├── config.php          # Frontend configuration
│   │   └── api.php             # API client classes (Patient, Medication, Vital, Flowsheet services)
│   ├── templates/
│   │   ├── header.php          # Page header
│   │   ├── patient-banner.php  # Patient demographics banner
│   │   ├── navigation.php      # Left navigation sidebar
│   │   └── right-panel.php     # Right panel (meds, orders, vitals)
│   ├── activities/
│   │   ├── flowsheets.php      # Flowsheets activity
│   │   ├── summary-index.php   # Patient summary index
│   │   ├── chart-review.php    # Document review
│   │   ├── results.php         # Lab results with trending
│   │   ├── detailed-vitals.php # Vital signs entry
│   │   ├── mar.php             # Medication Administration Record
│   │   ├── intake-output.php   # I/O tracking
│   │   ├── care-plan.php       # Care planning
│   │   ├── post-partum-hemorrhage.php  # PPH specialty view
│   │   ├── pediatrics.php      # Pediatric growth charts
│   │   ├── perinatal.php       # Labor & delivery tracking
│   │   ├── oncology.php        # Oncology treatment planning
│   │   └── infection-control.php  # Isolation & precautions
│   ├── admin/
│   │   └── roles.php           # Role & permission management
│   ├── assets/
│   │   ├── css/
│   │   │   └── epic-styles.css # Epic-style CSS
│   │   └── js/
│   │       └── epic-app.js     # Frontend JavaScript
│   └── api/
│       └── proxy.php           # API proxy
│
└── README.md                   # This file
```

## Prerequisites

### Backend Requirements
- Python 3.8+
- pip (Python package manager)

### Frontend Requirements
- PHP 7.4+ with cURL extension
- Apache or Nginx web server (recommended)
- OR PHP built-in server for development

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/EPIC-PHP-EHR-System.git
cd EPIC-PHP-EHR-System
```

### 2. Set Up Python Backend

```bash
# Navigate to backend directory
cd backend

# Create virtual environment (recommended)
python -m venv venv

# Activate virtual environment
# On Windows:
venv\Scripts\activate
# On macOS/Linux:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Run the backend server
python app.py
```

The backend will start on `http://localhost:5000`. On first run, it will create the SQLite database and seed it with sample data.

### 3. Set Up PHP Frontend

#### Option A: Using PHP Built-in Server (Development)

```bash
# In a new terminal, navigate to frontend directory
cd frontend

# Start PHP development server
php -S localhost:8080
```

Then open `http://localhost:8080` in your browser.

#### Option B: Using Apache (Recommended for Production)

1. Copy the frontend directory to your Apache document root
2. Enable required modules:
   ```bash
   sudo a2enmod proxy proxy_http rewrite
   ```
3. Use the provided `apache.conf` or add to your virtual host configuration
4. Restart Apache:
   ```bash
   sudo systemctl restart apache2
   ```

#### Option C: Using Nginx

Add to your Nginx server block:
```nginx
location / {
    root /path/to/frontend;
    index index.php;
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}

location /api {
    proxy_pass http://localhost:5000/api;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

## Configuration

### Backend Configuration (`backend/config.py`)

```python
# Database (default: SQLite)
SQLALCHEMY_DATABASE_URI = 'sqlite:///epic_ehr.db'

# For PostgreSQL:
# SQLALCHEMY_DATABASE_URI = 'postgresql://user:pass@localhost/epic_ehr'

# For MySQL:
# SQLALCHEMY_DATABASE_URI = 'mysql://user:pass@localhost/epic_ehr'
```

### Frontend Configuration (`frontend/includes/config.php`)

```php
// API endpoint
define('API_BASE_URL', 'http://localhost:5000/api');

// Session timeout (HIPAA compliance - 15 minutes)
define('SESSION_TIMEOUT', 900);
```

## Usage

### Accessing the Application

1. **Start the Python backend**:
   ```bash
   cd backend && python app.py
   ```

2. **Start the PHP frontend**:
   ```bash
   cd frontend && php -S localhost:8080
   ```

3. **Open in browser**: `http://localhost:8080`

4. **Login** with default credentials:

### Default Credentials

| Username | Password | Role | Department |
|----------|----------|------|------------|
| `admin` | `password123` | Administrator | IT |
| `drsmith` | `password123` | Physician | Internal Medicine |
| `nursejones` | `password123` | Nurse | Medical/Surgical |
| `drsandhu` | `password123` | Physician | OB/GYN |

> ⚠️ **Security Warning**: For production deployments, immediately change all default passwords or delete the seeded users and create new accounts via the admin interface.

### Key Workflows

#### Patient Chart Access
1. Search for a patient or select from patient list
2. Patient banner displays demographics, allergies, and code status
3. Navigate activities via left sidebar
4. Document in flowsheets, notes, or specialty modules

#### Medication Administration
1. Open MAR from patient chart
2. View scheduled medications by time
3. Click time slot to document administration
4. System tracks given, held, and missed doses

#### Order Entry
1. Navigate to Orders activity
2. Select from order sets or create individual orders
3. Orders route to appropriate departments
4. Track acknowledgment and completion

#### Clinical Documentation
1. Open Notes activity
2. Select note type (Progress Note, H&P, Discharge Summary)
3. Use SmartPhrases (`.VITALS`, `.LABS`, `.MEDS`) for quick insertion
4. Sign or pend note; co-sign if required

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/session` - Validate session

### Patients
- `GET /api/patients/` - List all patients
- `GET /api/patients/{id}` - Get patient by ID
- `GET /api/patients/search?q={query}` - Search patients
- `GET /api/patients/{id}/header` - Get combined header data
- `GET /api/patients/recent-admissions` - Recent admissions

### Medications
- `GET /api/medications/patient/{id}` - Get patient medications
- `GET /api/medications/patient/{id}/categorized` - Get medications by category
- `POST /api/medications/administration` - Record administration

### Orders
- `GET /api/orders/patient/{id}` - Get patient orders
- `POST /api/orders/` - Create new order
- `POST /api/orders/{id}/acknowledge` - Acknowledge order

### Vitals
- `GET /api/vitals/patient/{id}` - Get patient vitals
- `GET /api/vitals/patient/{id}/latest` - Get most recent vitals
- `GET /api/vitals/patient/{id}/trends` - Get vital sign trends
- `POST /api/vitals/` - Record new vitals

### Flowsheets
- `GET /api/flowsheets/groups` - Get available flowsheet groups
- `GET /api/flowsheets/patient/{id}/grouped` - Get flowsheet data grouped by section
- `GET /api/flowsheets/sections` - Get flowsheet sections
- `GET /api/flowsheets/templates` - Get flowsheet templates
- `POST /api/flowsheets/entry` - Create flowsheet entry

### Labs
- `GET /api/labs/patient/{id}` - Get lab results
- `GET /api/labs/patient/{id}/critical` - Get critical values
- `GET /api/labs/critical/pending` - Get pending critical results
- `POST /api/labs/{id}/acknowledge` - Acknowledge critical result

### Notes
- `GET /api/notes/patient/{id}` - Get patient notes
- `POST /api/notes/` - Create new note
- `POST /api/notes/{id}/sign` - Sign a note
- `POST /api/notes/{id}/cosign` - Co-sign a note

### Alerts & Tasks
- `GET /api/alerts/user/{id}` - Get user alerts
- `POST /api/alerts/{id}/dismiss` - Dismiss alert
- `POST /api/alerts/{id}/read` - Mark alert as read
- `GET /api/tasks/user/{id}` - Get user tasks
- `POST /api/tasks/{id}/complete` - Complete task

### Messages
- `GET /api/messages/unread/count` - Get unread message count

### Administrative
- `GET /api/admin/census` - Get facility census
- `GET /api/admin/departments` - Get department status
- `GET /api/scheduling/today` - Get today's schedule

### FHIR R4 Endpoints
- `GET /api/fhir/metadata` - FHIR CapabilityStatement
- `GET /api/fhir/Patient/{id}` - Get patient in FHIR format
- `GET /api/fhir/Encounter/{id}` - Get encounter in FHIR format
- `GET /api/fhir/Observation` - Get observations (vitals, labs)
- `GET /api/fhir/MedicationRequest` - Get medication orders

## Security & Compliance

### HIPAA Compliance Features
- **Audit Logging** - All PHI access is logged with user, timestamp, and action
- **Session Timeout** - Automatic logout after 15 minutes of inactivity
- **Role-Based Access** - Granular permissions by role and department
- **Break-the-Glass** - Emergency access with mandatory reason documentation
- **PHI Access Tracking** - Dedicated log for compliance reporting

### Authentication
- Session-based authentication with secure cookies
- Password hashing with industry-standard algorithms
- Failed login attempt tracking
- Account lockout after multiple failures

## Customization

### Adding New Flowsheet Groups

1. Edit `backend/routes/flowsheet_routes.py` - Add to the `get_flowsheet_groups()` function
2. Create corresponding section configuration
3. Add frontend activity page if needed

### Styling

All styles are in `frontend/assets/css/epic-styles.css`. Key CSS variables:
```css
:root {
    --primary-color: #0066cc;
    --secondary-color: #004499;
    --accent-color: #00aa55;
    --warning-color: #ff9900;
    --danger-color: #cc0000;
}
```

### Database Migration

To switch to PostgreSQL:

1. Update `backend/config.py`:
   ```python
   SQLALCHEMY_DATABASE_URI = 'postgresql://user:pass@localhost/epic_ehr'
   ```

2. Install psycopg2:
   ```bash
   pip install psycopg2-binary
   ```

3. Run the application to create tables

## Integration

### HL7 v2.5.1
The system includes an HL7 processor (`backend/integrations/hl7_processor.py`) supporting:
- **ADT** - Admit/Discharge/Transfer messages
- **ORM** - Order messages
- **ORU** - Observation results
- **MDM** - Document management

### FHIR R4
RESTful FHIR endpoints available at `/api/fhir/` for:
- Patient resources
- Encounter resources
- Observation resources (vitals, labs)
- MedicationRequest resources
- DocumentReference resources

## Known Limitations

- Sample data is provided for demonstration; production deployments should start with empty database
- HL7 integration requires external interface engine for message routing
- Some specialty modules have limited functionality

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

## License

This project is for educational and development purposes. Epic and Hyperspace are trademarks of Epic Systems Corporation. This project is not affiliated with or endorsed by Epic Systems.

## Acknowledgments

- Inspired by Epic Systems' Hyperspace interface
- Built with Flask, SQLAlchemy, PHP, and vanilla JavaScript
- UI patterns based on common EHR workflows
- FHIR implementation follows HL7 FHIR R4 specifications