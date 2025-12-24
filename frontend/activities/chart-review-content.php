<?php
/**
 * Chart Review Tab Content
 */
?>
<div class="content-columns">
    <div class="content-column" style="flex: 0 0 200px;">
        <!-- Document Navigator -->
        <div class="content-panel" style="height: calc(100vh - 220px); overflow-y: auto;">
            <div class="panel-header gray">
                <span>Documents</span>
            </div>
            <div class="panel-content" style="padding: 0;">
                <div style="border-bottom: 1px solid #ddd;">
                    <div style="padding: 8px; background: #f0f4f8; font-weight: 600; font-size: 11px; cursor: pointer;">
                        <i class="fas fa-caret-down"></i> This Encounter
                    </div>
                    <div style="padding-left: 15px; font-size: 11px;">
                        <a href="#" style="display: block; padding: 4px 8px; background: #e0f0ff;">Progress Note (Today)</a>
                        <a href="#" style="display: block; padding: 4px 8px;">Nursing Note (Today)</a>
                        <a href="#" style="display: block; padding: 4px 8px;">H&P (Yesterday)</a>
                        <a href="#" style="display: block; padding: 4px 8px;">Admission Note</a>
                    </div>
                </div>
                <div style="border-bottom: 1px solid #ddd;">
                    <div style="padding: 8px; background: #f0f4f8; font-weight: 600; font-size: 11px; cursor: pointer;">
                        <i class="fas fa-caret-right"></i> Previous Encounters
                    </div>
                </div>
                <div style="border-bottom: 1px solid #ddd;">
                    <div style="padding: 8px; background: #f0f4f8; font-weight: 600; font-size: 11px; cursor: pointer;">
                        <i class="fas fa-caret-right"></i> Results
                    </div>
                </div>
                <div style="border-bottom: 1px solid #ddd;">
                    <div style="padding: 8px; background: #f0f4f8; font-weight: 600; font-size: 11px; cursor: pointer;">
                        <i class="fas fa-caret-right"></i> Imaging
                    </div>
                </div>
                <div style="border-bottom: 1px solid #ddd;">
                    <div style="padding: 8px; background: #f0f4f8; font-weight: 600; font-size: 11px; cursor: pointer;">
                        <i class="fas fa-caret-right"></i> Procedures
                    </div>
                </div>
                <div>
                    <div style="padding: 8px; background: #f0f4f8; font-weight: 600; font-size: 11px; cursor: pointer;">
                        <i class="fas fa-caret-right"></i> Outside Records
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-column">
        <!-- Document Viewer -->
        <div class="content-panel" style="height: calc(100vh - 220px); overflow-y: auto;">
            <div class="panel-header blue">
                <span><i class="fas fa-file-medical-alt"></i> Progress Note</span>
                <div class="panel-header-actions">
                    <a href="#">Print</a>
                    <a href="#">Copy Forward</a>
                </div>
            </div>
            <div class="panel-content">
                <div style="background: #f8f8f8; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
                    <table style="font-size: 11px; width: 100%;">
                        <tr>
                            <td style="width: 100px;"><strong>Author:</strong></td>
                            <td>Wilson, Sarah MD</td>
                            <td style="width: 100px;"><strong>Service:</strong></td>
                            <td>Internal Medicine</td>
                        </tr>
                        <tr>
                            <td><strong>Signed:</strong></td>
                            <td>Today 07:35</td>
                            <td><strong>Encounter:</strong></td>
                            <td>Inpatient</td>
                        </tr>
                    </table>
                </div>
                
                <div style="font-size: 12px; line-height: 1.7;">
                    <h4 style="border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-bottom: 10px;">SUBJECTIVE</h4>
                    <p style="margin-left: 15px;">
                        68 y.o. male admitted for community-acquired pneumonia. Day 3 of hospitalization.
                        Patient reports significant improvement in dyspnea overnight. Able to walk to bathroom without becoming short of breath.
                        Cough is productive with white-yellow sputum, less frequent than yesterday.
                        Denies fever, chills, chest pain, or hemoptysis. Pain is well controlled at 3/10 with current regimen.
                        Slept well overnight without supplemental oxygen. Good appetite for breakfast - ate 75% of meal.
                        Voiding without difficulty via Foley. Last BM yesterday.
                    </p>
                    
                    <h4 style="border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 15px 0 10px 0;">OBJECTIVE</h4>
                    <div style="margin-left: 15px;">
                        <p><strong>Vitals:</strong> T 98.6°F, BP 158/92 (elevated), HR 88 irregular, RR 18, SpO2 96% on RA</p>
                        <p><strong>General:</strong> Alert, oriented x4, pleasant, NAD, conversant, sitting up in bed</p>
                        <p><strong>HEENT:</strong> NCAT, PERRL, EOMI, MMM, OP clear without erythema or exudate</p>
                        <p><strong>Neck:</strong> Supple, no LAD, no JVD</p>
                        <p><strong>CV:</strong> Irregularly irregular rhythm, no murmurs/rubs/gallops, no peripheral edema</p>
                        <p><strong>Pulm:</strong> Clear to auscultation bilaterally, improved air movement compared to admission. No wheezes, rhonchi, or rales. Good inspiratory effort.</p>
                        <p><strong>Abd:</strong> Soft, non-tender, non-distended, normoactive bowel sounds</p>
                        <p><strong>Ext:</strong> Warm, well-perfused, no edema, 2+ pedal pulses</p>
                        <p><strong>Neuro:</strong> A&O x4, CN II-XII intact, strength 5/5 all extremities, sensation intact</p>
                        <p><strong>Skin:</strong> Warm, dry, intact. No rashes or pressure injuries.</p>
                    </div>
                    
                    <h4 style="border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 15px 0 10px 0;">STUDIES/LABS</h4>
                    <div style="margin-left: 15px;">
                        <p><strong>Labs (this AM):</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>WBC 12.5 (↓ from 15.2 on admission) - improving</li>
                            <li>Hgb 11.2, Hct 34.5, Plt 225</li>
                            <li>Na 138, K 4.2, Cl 102, CO2 24</li>
                            <li>BUN 28, Cr 1.8 (stable at baseline)</li>
                            <li>Glucose 186 (elevated - stress hyperglycemia)</li>
                        </ul>
                        <p><strong>Imaging:</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>CXR (today): Improved bilateral infiltrates. No pleural effusion.</li>
                        </ul>
                        <p><strong>Micro:</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>Blood cultures (x2) - 24 hrs, no growth to date</li>
                            <li>Sputum culture - pending final</li>
                        </ul>
                    </div>
                    
                    <h4 style="border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 15px 0 10px 0;">ASSESSMENT & PLAN</h4>
                    <div style="margin-left: 15px;">
                        <p><strong>1. Community-acquired pneumonia</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>Clinically improving - WBC trending down, afebrile, lungs clearing</li>
                            <li>Continue Vancomycin 1g IV q12h (await cultures for de-escalation)</li>
                            <li>If cultures remain negative, consider switch to oral antibiotics tomorrow</li>
                            <li>Encourage incentive spirometry, cough/deep breathing</li>
                        </ul>
                        
                        <p><strong>2. Type 2 Diabetes Mellitus</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>Glucose remains elevated (stress hyperglycemia)</li>
                            <li>Continue home Metformin 500mg TID</li>
                            <li>Add sliding scale insulin coverage</li>
                            <li>Check A1C - pending</li>
                            <li>Diabetes educator consult for reinforcement</li>
                        </ul>
                        
                        <p><strong>3. Hypertension</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>BP elevated today at 158/92</li>
                            <li>Continue Lisinopril 10mg daily and Metoprolol 25mg daily</li>
                            <li>May need to uptitrate as outpatient</li>
                        </ul>
                        
                        <p><strong>4. Chronic Kidney Disease Stage 3</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>Creatinine stable at 1.8 (baseline)</li>
                            <li>Continue to avoid nephrotoxins</li>
                            <li>Monitor with daily BMP</li>
                        </ul>
                        
                        <p><strong>5. Atrial fibrillation on anticoagulation</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>Rate controlled (HR 88)</li>
                            <li>Continue Apixaban 5mg BID</li>
                            <li>Continue Metoprolol for rate control</li>
                        </ul>
                        
                        <p><strong>6. Anemia (chronic)</strong></p>
                        <ul style="margin: 5px 0 10px 20px;">
                            <li>Hgb 11.2 - stable, consistent with CKD</li>
                            <li>No acute bleeding</li>
                            <li>Continue to monitor</li>
                        </ul>
                    </div>
                    
                    <h4 style="border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 15px 0 10px 0;">DISPOSITION</h4>
                    <p style="margin-left: 15px;">
                        Continue inpatient management. Patient is improving and may be appropriate for discharge in 1-2 days 
                        if continues current trajectory. Will reassess tomorrow. Family meeting scheduled for discharge planning.
                    </p>
                    
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                        <p><strong>Electronically signed by:</strong> Wilson, Sarah MD</p>
                        <p><strong>Date/Time:</strong> Today 07:35</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
