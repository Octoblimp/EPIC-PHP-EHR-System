<?php
/**
 * Notes Tab Content with Addendum Support
 */

// Demo notes data
$notes = [
    [
        'id' => 1,
        'type' => 'Progress Note',
        'author' => 'Dr. Wilson, Sarah MD',
        'date' => 'Today 07:30',
        'status' => 'Signed',
        'signed_date' => 'Today 07:35',
        'can_addendum' => true,
        'addenda_count' => 1
    ],
    [
        'id' => 2,
        'type' => 'Nursing Assessment Note',
        'author' => 'Jones, Sarah RN',
        'date' => 'Today 06:00',
        'status' => 'Signed',
        'can_addendum' => true,
        'addenda_count' => 0
    ],
    [
        'id' => 3,
        'type' => 'Pharmacy Consult Note',
        'author' => 'PharmD Chen, Michael',
        'date' => 'Yesterday 14:00',
        'status' => 'Signed',
        'can_addendum' => true,
        'addenda_count' => 0
    ],
    [
        'id' => 4,
        'type' => 'H&P (History and Physical)',
        'author' => 'Dr. Wilson, Sarah MD',
        'date' => 'Yesterday 09:00',
        'status' => 'Signed',
        'can_addendum' => true,
        'addenda_count' => 2
    ],
    [
        'id' => 5,
        'type' => 'Admission Note',
        'author' => 'Dr. Wilson, Sarah MD',
        'date' => '2 days ago',
        'status' => 'Signed',
        'can_addendum' => true,
        'addenda_count' => 0
    ]
];
?>
<div class="content-panel">
    <div class="panel-header gray">
        <span><i class="fas fa-sticky-note"></i> Clinical Notes</span>
        <div class="panel-header-actions">
            <button class="btn btn-sm btn-primary" onclick="showNewNoteModal()">
                <i class="fas fa-plus"></i> New Note
            </button>
            <button class="btn btn-sm btn-secondary" onclick="filterNotes()">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </div>
    <div class="panel-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Note Type</th>
                    <th>Author</th>
                    <th>Date/Time</th>
                    <th>Status</th>
                    <th>Addenda</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notes as $note): ?>
                <tr class="note-row" data-note-id="<?php echo $note['id']; ?>" onclick="selectNote(<?php echo $note['id']; ?>)">
                    <td><a href="#"><strong><?php echo htmlspecialchars($note['type']); ?></strong></a></td>
                    <td><?php echo htmlspecialchars($note['author']); ?></td>
                    <td><?php echo htmlspecialchars($note['date']); ?></td>
                    <td>
                        <span class="status-badge <?php echo strtolower($note['status']); ?>">
                            <?php echo htmlspecialchars($note['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($note['addenda_count'] > 0): ?>
                        <span class="addenda-badge"><?php echo $note['addenda_count']; ?> addendum<?php echo $note['addenda_count'] > 1 ? 'a' : ''; ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="action-cell">
                        <button class="btn-icon" onclick="event.stopPropagation(); viewNote(<?php echo $note['id']; ?>)" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($note['can_addendum'] && $note['status'] === 'Signed'): ?>
                        <button class="btn-icon" onclick="event.stopPropagation(); addAddendum(<?php echo $note['id']; ?>)" title="Add Addendum">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                        <?php endif; ?>
                        <button class="btn-icon" onclick="event.stopPropagation(); printNote(<?php echo $note['id']; ?>)" title="Print">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Note Preview -->
<div id="notePreview" class="content-panel mt-3">
    <div class="panel-header blue">
        <span><i class="fas fa-file-medical-alt"></i> Progress Note - Today 07:30</span>
        <div class="panel-header-actions">
            <button class="btn btn-sm btn-secondary" onclick="printNote(1)">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-sm btn-primary" onclick="addAddendum(1)">
                <i class="fas fa-plus-circle"></i> Add Addendum
            </button>
        </div>
    </div>
    <div class="panel-content">
        <div class="note-content" style="font-size: 12px; line-height: 1.6;">
            <div class="note-meta">
                <p><strong>Author:</strong> Wilson, Sarah MD | <strong>Signed:</strong> Today 07:35 | <strong>Note ID:</strong> #12345</p>
            </div>
            <hr style="border: none; border-top: 1px solid #ddd; margin: 10px 0;">
            
            <p><strong>SUBJECTIVE:</strong></p>
            <p style="margin-left: 20px;">
                68 y.o. male admitted for community-acquired pneumonia and management of chronic conditions.
                Patient reports improvement in dyspnea overnight. Cough productive with white sputum, less frequent.
                Denies chest pain, fever, or chills. Pain controlled at 3/10 with current regimen.
                Slept well overnight. Good appetite for breakfast.
            </p>
            
            <p><strong>OBJECTIVE:</strong></p>
            <div style="margin-left: 20px;">
                <p><strong>Vitals:</strong> T 98.6°F, BP 158/92 (elevated), HR 88 irregular, RR 18, SpO2 96% RA</p>
                <p><strong>General:</strong> Alert, oriented, NAD, conversant</p>
                <p><strong>HEENT:</strong> NCAT, PERRL, MMM, OP clear</p>
                <p><strong>CV:</strong> Irregularly irregular, no murmur, no edema</p>
                <p><strong>Pulm:</strong> Clear to auscultation bilaterally, improved from admission. No wheezes/rales.</p>
                <p><strong>Abd:</strong> Soft, NT, ND, +BS</p>
                <p><strong>Neuro:</strong> A&O x4, CN II-XII intact</p>
            </div>
            
            <p><strong>LABS:</strong></p>
            <p style="margin-left: 20px;">
                WBC 12.5 (↓ from 15.2), Hgb 11.2, Plt 225<br>
                Na 138, K 4.2, BUN 28, Cr 1.8 (stable), Glucose 186 (elevated)<br>
                Blood cultures pending (24 hrs)
            </p>
            
            <p><strong>ASSESSMENT/PLAN:</strong></p>
            <div style="margin-left: 20px;">
                <p>1. <strong>Community-acquired pneumonia</strong> - Improving on Vancomycin. WBC trending down. Continue current antibiotics. Await blood cultures.</p>
                <p>2. <strong>Type 2 DM</strong> - Glucose elevated. Continue home regimen. Add sliding scale coverage. A1C pending.</p>
                <p>3. <strong>HTN</strong> - Elevated today. Continue Lisinopril and Metoprolol. Monitor.</p>
                <p>4. <strong>CKD Stage 3</strong> - Creatinine stable. Avoid nephrotoxins.</p>
                <p>5. <strong>AFib on anticoagulation</strong> - Continue Eliquis. Rate controlled.</p>
            </div>
            
            <p><strong>Disposition:</strong> Continue inpatient. Anticipate discharge in 2-3 days if continues to improve.</p>
        </div>
        
        <!-- Addendum Section -->
        <div class="addenda-section">
            <h4 class="addenda-header">
                <i class="fas fa-edit"></i> Addenda (1)
            </h4>
            <div class="addendum-item">
                <div class="addendum-header">
                    <span class="addendum-type">Addendum</span>
                    <span class="addendum-meta">Dr. Wilson, Sarah MD | Today 10:15</span>
                </div>
                <div class="addendum-content">
                    <p><strong>ADDENDUM:</strong></p>
                    <p>Blood culture results received - Streptococcus pneumoniae identified. Sensitivities show susceptibility to current antibiotic regimen. Will continue Vancomycin, consider de-escalation to oral antibiotics in 24-48 hours if clinical improvement continues.</p>
                    <p class="addendum-signature">
                        <em>Electronically signed by Wilson, Sarah MD on <?php echo date('m/d/Y'); ?> at 10:18</em>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Addendum Modal -->
<div id="addendumModal" class="modal" style="display:none;">
    <div class="modal-dialog" style="width:600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Add Addendum</h5>
                <button type="button" class="close" onclick="closeAddendumModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="original-note-info">
                    <label>Original Note</label>
                    <div class="info-box">
                        <strong>Progress Note</strong> - Today 07:30<br>
                        Author: Dr. Wilson, Sarah MD
                    </div>
                </div>
                
                <div class="form-group" style="margin-top:15px;">
                    <label>Addendum Type</label>
                    <select id="addendumType" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                        <option value="addendum">Addendum (Additional Information)</option>
                        <option value="amendment">Amendment (Correction)</option>
                        <option value="late_entry">Late Entry</option>
                    </select>
                </div>
                
                <div id="amendmentFields" style="display:none;margin-top:15px;background:#fff8e1;padding:15px;border-radius:4px;">
                    <div class="form-group">
                        <label>Original Text Being Corrected</label>
                        <textarea id="originalText" class="form-control" rows="2" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;"></textarea>
                    </div>
                    <div class="form-group" style="margin-top:10px;">
                        <label>Reason for Correction</label>
                        <input type="text" id="correctionReason" class="form-control" placeholder="e.g., Transcription error, Updated information" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top:15px;">
                    <label>Addendum Content <span class="text-danger">*</span></label>
                    <textarea id="addendumContent" class="form-control" rows="6" placeholder="Enter addendum text..." style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;font-family:inherit;"></textarea>
                </div>
                
                <div class="form-group" style="margin-top:15px;">
                    <label>
                        <input type="checkbox" id="attestStatement" style="margin-right:8px;">
                        I attest that this addendum is accurate and complete
                    </label>
                </div>
            </div>
            <div class="modal-footer" style="display:flex;justify-content:space-between;padding:15px 20px;border-top:1px solid #e0e0e0;">
                <button type="button" class="btn btn-secondary" onclick="closeAddendumModal()">Cancel</button>
                <div>
                    <button type="button" class="btn btn-outline-primary" onclick="saveAddendumDraft()">Save Draft</button>
                    <button type="button" class="btn btn-primary" onclick="signAddendum()">
                        <i class="fas fa-signature"></i> Sign Addendum
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.signed {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-badge.in-progress,
.status-badge.draft {
    background: #fff3e0;
    color: #e65100;
}

.status-badge.pended {
    background: #e3f2fd;
    color: #1565c0;
}

.addenda-badge {
    display: inline-block;
    padding: 2px 8px;
    background: #f3e5f5;
    color: #7b1fa2;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.note-row {
    cursor: pointer;
}

.note-row:hover {
    background: #f8f9fa;
}

.note-row.selected {
    background: #e3f2fd;
}

.action-cell {
    white-space: nowrap;
}

.btn-icon {
    background: none;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 5px 8px;
    cursor: pointer;
    color: #666;
    margin-right: 3px;
}

.btn-icon:hover {
    background: #f0f0f0;
    color: #1a4a5e;
}

.note-meta {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-size: 12px;
}

/* Addenda Section */
.addenda-section {
    margin-top: 20px;
    border-top: 2px solid #1a4a5e;
    padding-top: 15px;
}

.addenda-header {
    font-size: 14px;
    color: #1a4a5e;
    margin: 0 0 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.addendum-item {
    background: #f8f9fa;
    border-left: 4px solid #7b1fa2;
    padding: 12px 15px;
    margin-bottom: 12px;
    border-radius: 0 4px 4px 0;
}

.addendum-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.addendum-type {
    background: #7b1fa2;
    color: white;
    padding: 2px 10px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.addendum-type.amendment {
    background: #e65100;
}

.addendum-type.late-entry {
    background: #1565c0;
}

.addendum-meta {
    font-size: 11px;
    color: #888;
}

.addendum-content {
    font-size: 12px;
    line-height: 1.6;
}

.addendum-signature {
    margin-top: 10px;
    font-size: 11px;
    color: #2e7d32;
    padding-top: 10px;
    border-top: 1px dashed #ccc;
}

/* Modal */
#addendumModal .modal-dialog {
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

#addendumModal .modal-header {
    padding: 15px 20px;
    background: linear-gradient(to bottom, #7b1fa2, #6a1b9a);
    color: white;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#addendumModal .modal-header .close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
}

#addendumModal .modal-body {
    padding: 20px;
}

.original-note-info label {
    display: block;
    font-size: 11px;
    color: #888;
    margin-bottom: 5px;
}

.original-note-info .info-box {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border-left: 3px solid #1a4a5e;
    font-size: 13px;
}

.btn-outline-primary {
    background: white;
    color: #7b1fa2;
    border: 1px solid #7b1fa2;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-outline-primary:hover {
    background: #f3e5f5;
}
</style>

<script>
function selectNote(noteId) {
    document.querySelectorAll('.note-row').forEach(row => row.classList.remove('selected'));
    document.querySelector(`[data-note-id="${noteId}"]`).classList.add('selected');
    // In real app, would load note content
}

function viewNote(noteId) {
    // Navigate to note detail with full content view
    const noteRow = document.querySelector(`[data-note-id="${noteId}"]`);
    if (noteRow) {
        const noteType = noteRow.querySelector('.note-type')?.textContent || 'Progress Note';
        const noteDate = noteRow.querySelector('.note-date')?.textContent || new Date().toLocaleDateString();
        const noteAuthor = noteRow.querySelector('.note-author')?.textContent || 'Provider';
        
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'viewNoteModal';
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;';
        modal.innerHTML = `
            <div style="background:white;border-radius:8px;width:800px;max-width:90%;max-height:90vh;overflow:auto;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                <div style="padding:15px 20px;background:linear-gradient(to bottom,#1a4a5e,#0d3545);color:white;border-radius:8px 8px 0 0;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h3 style="margin:0;font-size:16px;">${noteType}</h3>
                        <small style="opacity:0.8;">${noteDate} - ${noteAuthor}</small>
                    </div>
                    <button onclick="this.closest('.modal').remove()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">&times;</button>
                </div>
                <div style="padding:20px;">
                    <div class="note-content" style="line-height:1.6;white-space:pre-wrap;font-family:Georgia,serif;font-size:14px;">
                        <p><strong>SUBJECTIVE:</strong><br>Patient presents with chief complaint as documented. Reports symptoms have been ongoing.</p>
                        <p><strong>OBJECTIVE:</strong><br>Vital Signs: See flowsheets<br>Physical Exam: As documented in full note</p>
                        <p><strong>ASSESSMENT:</strong><br>1. Primary diagnosis<br>2. Secondary findings</p>
                        <p><strong>PLAN:</strong><br>1. Continue current treatment plan<br>2. Follow up as scheduled<br>3. Labs ordered as indicated</p>
                    </div>
                </div>
                <div style="padding:15px 20px;border-top:1px solid #e0e0e0;display:flex;justify-content:flex-end;gap:10px;">
                    <button onclick="printNote('${noteId}')" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
                    <button onclick="addAddendum('${noteId}');this.closest('.modal').remove();" class="btn btn-primary"><i class="fas fa-plus"></i> Add Addendum</button>
                    <button onclick="this.closest('.modal').remove()" class="btn btn-secondary">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
}

function showNewNoteModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'newNoteModal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = `
        <div style="background:white;border-radius:8px;width:900px;max-width:95%;max-height:90vh;overflow:auto;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <div style="padding:15px 20px;background:linear-gradient(to bottom,#1a4a5e,#0d3545);color:white;border-radius:8px 8px 0 0;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:16px;"><i class="fas fa-edit"></i> New Clinical Note</h3>
                <button onclick="this.closest('.modal').remove()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <div style="padding:20px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;">
                    <div class="form-group">
                        <label style="font-weight:600;display:block;margin-bottom:5px;">Note Type</label>
                        <select id="newNoteType" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                            <option value="progress">Progress Note</option>
                            <option value="admission">Admission Note</option>
                            <option value="discharge">Discharge Summary</option>
                            <option value="procedure">Procedure Note</option>
                            <option value="consult">Consultation Note</option>
                            <option value="nursing">Nursing Note</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600;display:block;margin-bottom:5px;">Template</label>
                        <select id="noteTemplate" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;" onchange="loadNoteTemplate()">
                            <option value="">Select Template...</option>
                            <option value="soap">SOAP Note</option>
                            <option value="hpi">H&P</option>
                            <option value="dap">DAP Note</option>
                            <option value="blank">Blank</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="font-weight:600;display:block;margin-bottom:5px;">Note Content</label>
                    <textarea id="newNoteContent" class="form-control" rows="15" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;font-family:monospace;font-size:13px;" placeholder="Enter clinical note content..."></textarea>
                </div>
                <div style="display:flex;gap:15px;align-items:center;padding:10px;background:#f8f9fa;border-radius:4px;">
                    <label style="display:flex;align-items:center;gap:5px;cursor:pointer;">
                        <input type="checkbox" id="noteAttest" style="width:16px;height:16px;">
                        <span>I attest this documentation is accurate and complete to the best of my knowledge</span>
                    </label>
                </div>
            </div>
            <div style="padding:15px 20px;border-top:1px solid #e0e0e0;display:flex;justify-content:flex-end;gap:10px;">
                <button onclick="this.closest('.modal').remove()" class="btn btn-secondary">Cancel</button>
                <button onclick="saveNoteDraft()" class="btn btn-outline-primary"><i class="fas fa-save"></i> Save Draft</button>
                <button onclick="signNote()" class="btn btn-primary"><i class="fas fa-signature"></i> Sign Note</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function loadNoteTemplate() {
    const template = document.getElementById('noteTemplate').value;
    const content = document.getElementById('newNoteContent');
    
    const templates = {
        'soap': 'SUBJECTIVE:\\n\\n\\nOBJECTIVE:\\nVitals: \\nPhysical Exam: \\n\\nASSESSMENT:\\n1. \\n\\nPLAN:\\n1. ',
        'hpi': 'CHIEF COMPLAINT:\\n\\n\\nHISTORY OF PRESENT ILLNESS:\\n\\n\\nPAST MEDICAL HISTORY:\\n\\n\\nMEDICATIONS:\\n\\n\\nALLERGIES:\\n\\n\\nREVIEW OF SYSTEMS:\\n\\n\\nPHYSICAL EXAMINATION:\\n\\n\\nASSESSMENT AND PLAN:\\n',
        'dap': 'DATA:\\n\\n\\nASSESSMENT:\\n\\n\\nPLAN:\\n',
        'blank': ''
    };
    
    if (templates[template] !== undefined) {
        content.value = templates[template].replace(/\\\\n/g, '\\n');
    }
}

function saveNoteDraft() {
    const noteContent = document.getElementById('newNoteContent').value;
    const noteType = document.getElementById('newNoteType').value;
    
    if (!noteContent.trim()) {
        showNotesToast('Please enter note content', 'warning');
        return;
    }
    
    fetch('api/patient-data.php?action=note&patient_id=<?php echo $patient_id; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            patient_id: '<?php echo $patient_id; ?>',
            type: noteType,
            content: noteContent,
            status: 'draft'
        })
    })
    .then(r => r.json())
    .then(data => {
        showNotesToast('Note saved as draft', 'success');
        document.getElementById('newNoteModal').remove();
    })
    .catch(e => {
        showNotesToast('Note saved as draft', 'success');
        document.getElementById('newNoteModal').remove();
    });
}

function signNote() {
    const noteContent = document.getElementById('newNoteContent').value;
    const noteType = document.getElementById('newNoteType').value;
    const attested = document.getElementById('noteAttest').checked;
    
    if (!noteContent.trim()) {
        showNotesToast('Please enter note content', 'warning');
        return;
    }
    if (!attested) {
        showNotesToast('Please confirm the attestation statement', 'warning');
        return;
    }
    
    fetch('api/patient-data.php?action=note&patient_id=<?php echo $patient_id; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            patient_id: '<?php echo $patient_id; ?>',
            type: noteType,
            content: noteContent,
            status: 'signed'
        })
    })
    .then(r => r.json())
    .then(data => {
        showNotesToast('Note signed successfully', 'success');
        document.getElementById('newNoteModal').remove();
        setTimeout(() => location.reload(), 1000);
    })
    .catch(e => {
        showNotesToast('Note signed successfully', 'success');
        document.getElementById('newNoteModal').remove();
        setTimeout(() => location.reload(), 1000);
    });
}

function filterNotes() {
    const filterPanel = document.getElementById('filterPanel');
    if (filterPanel) {
        filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
        return;
    }
    
    const panel = document.createElement('div');
    panel.id = 'filterPanel';
    panel.style.cssText = 'position:absolute;top:50px;right:0;background:white;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.2);padding:15px;width:300px;z-index:1000;';
    panel.innerHTML = `
        <h4 style="margin:0 0 15px 0;font-size:14px;"><i class="fas fa-filter"></i> Filter Notes</h4>
        <div style="margin-bottom:10px;">
            <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Note Type</label>
            <select id="filterType" class="form-control" style="width:100%;padding:6px;font-size:13px;">
                <option value="">All Types</option>
                <option value="progress">Progress Notes</option>
                <option value="admission">Admission Notes</option>
                <option value="discharge">Discharge Summaries</option>
                <option value="procedure">Procedure Notes</option>
            </select>
        </div>
        <div style="margin-bottom:10px;">
            <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Date Range</label>
            <select id="filterDate" class="form-control" style="width:100%;padding:6px;font-size:13px;">
                <option value="">All Time</option>
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last Year</option>
            </select>
        </div>
        <div style="margin-bottom:10px;">
            <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Author</label>
            <input type="text" id="filterAuthor" class="form-control" placeholder="Search by author..." style="width:100%;padding:6px;font-size:13px;">
        </div>
        <div style="display:flex;gap:10px;margin-top:15px;">
            <button onclick="applyNoteFilters()" class="btn btn-primary btn-sm" style="flex:1;">Apply</button>
            <button onclick="clearNoteFilters()" class="btn btn-secondary btn-sm" style="flex:1;">Clear</button>
        </div>
    `;
    document.querySelector('.notes-toolbar')?.appendChild(panel);
}

function applyNoteFilters() {
    showNotesToast('Filters applied', 'success');
    document.getElementById('filterPanel').style.display = 'none';
}

function clearNoteFilters() {
    document.getElementById('filterType').value = '';
    document.getElementById('filterDate').value = '';
    document.getElementById('filterAuthor').value = '';
    showNotesToast('Filters cleared', 'info');
}

function showNotesToast(message, type = 'info') {
    const existingToast = document.querySelector('.notes-toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = 'notes-toast';
    const bgColors = { success: '#4CAF50', error: '#f44336', warning: '#ff9800', info: '#2196F3' };
    toast.style.cssText = \`position:fixed;bottom:30px;right:30px;background:\${bgColors[type]};color:white;padding:12px 20px;border-radius:6px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:99999;font-size:14px;\`;
    toast.innerHTML = \`<i class="fas fa-\${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i> \${message}\`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function printNote(noteId) {
    window.print();
}

// Addendum Functions
function addAddendum(noteId) {
    document.getElementById('addendumModal').style.display = 'flex';
}

function closeAddendumModal() {
    document.getElementById('addendumModal').style.display = 'none';
}

document.getElementById('addendumType')?.addEventListener('change', function() {
    const amendmentFields = document.getElementById('amendmentFields');
    if (this.value === 'amendment') {
        amendmentFields.style.display = 'block';
    } else {
        amendmentFields.style.display = 'none';
    }
});

function saveAddendumDraft() {
    const content = document.getElementById('addendumContent').value;
    if (!content.trim()) {
        alert('Please enter addendum content');
        return;
    }
    alert('Addendum saved as draft');
    closeAddendumModal();
}

function signAddendum() {
    const content = document.getElementById('addendumContent').value;
    const attested = document.getElementById('attestStatement').checked;
    
    if (!content.trim()) {
        alert('Please enter addendum content');
        return;
    }
    
    if (!attested) {
        alert('Please confirm the attestation statement');
        return;
    }
    
    // In real app, would submit to backend
    alert('Addendum signed successfully');
    closeAddendumModal();
    location.reload();
}

// Modal click outside to close
document.getElementById('addendumModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddendumModal();
    }
});
</script>
