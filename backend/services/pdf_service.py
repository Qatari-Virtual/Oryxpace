import io
import os
import json
from django.conf import settings
from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, Image
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.pdfgen import canvas
from reportlab.graphics.shapes import Drawing, Rect, Line, String, Polygon

class NumberedCanvas(canvas.Canvas):
    """
    Two-pass canvas to dynamically compute and render 'Page X of Y' footers.
    """
    def __init__(self, *args, **kwargs):
        super(NumberedCanvas, self).__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super(NumberedCanvas, self).showPage()
        super(NumberedCanvas, self).save()

    def draw_page_decorations(self, page_count):
        self.saveState()
        self.setFont("Helvetica", 8)
        self.setFillColor(colors.HexColor("#64748b")) # slate-500
        
        # Footer text
        footer_text = f"Page {self._pageNumber} of {page_count}"
        self.drawRightString(letter[0] - 54, 30, footer_text)
        
        system_text = "Oryxspace - Flight Training Evaluation Record"
        self.drawString(54, 30, system_text)
        
        # Header thin rule & category title (only on pages > 1)
        if self._pageNumber > 1:
            self.setStrokeColor(colors.HexColor("#e2e8f0")) # slate-200
            self.setLineWidth(0.5)
            self.line(54, letter[1] - 40, letter[0] - 54, letter[1] - 40)
            self.drawString(54, letter[1] - 34, "Trainee Performance Feedback Report")
            self.drawRightString(letter[0] - 54, letter[1] - 34, "CONFIDENTIAL")
            
        self.restoreState()

class RadarChart(Drawing):
    """
    Custom 4-axis radar chart showing Communication, Handling, Systems, and Ground Ops.
    """
    def __init__(self, comm, hand, sys, gro):
        super(RadarChart, self).__init__(220, 190)
        cx, cy = 110, 90
        r_max = 65
        
        # Grid lines (diamonds) at 25%, 50%, 75%, 100%
        for pct in [25, 50, 75, 100]:
            r = r_max * pct / 100.0
            # Draw diamond
            self.add(Polygon(
                [cx, cy + r, cx + r, cy, cx, cy - r, cx - r, cy],
                strokeColor=colors.HexColor("#E2E8F0"),
                strokeWidth=0.5,
                fillColor=None
            ))
            # Grid label
            if pct in [50, 100]:
                self.add(String(cx + 2, cy + r - 8, f"{pct}%", fontSize=6, fontName="Helvetica", fillColor=colors.HexColor("#94A3B8")))
        
        # Axis lines
        self.add(Line(cx, cy - r_max, cx, cy + r_max, strokeColor=colors.HexColor("#E2E8F0"), strokeWidth=0.5))
        self.add(Line(cx - r_max, cy, cx + r_max, cy, strokeColor=colors.HexColor("#E2E8F0"), strokeWidth=0.5))
        
        # Axis labels
        self.add(String(cx - 30, cy + r_max + 6, "Communication", fontName="Helvetica-Bold", fontSize=8, fillColor=colors.HexColor("#5C0E3E")))
        self.add(String(cx + r_max + 6, cy - 3, "Handling", fontName="Helvetica-Bold", fontSize=8, fillColor=colors.HexColor("#5C0E3E")))
        self.add(String(cx - 18, cy - r_max - 12, "Systems", fontName="Helvetica-Bold", fontSize=8, fillColor=colors.HexColor("#5C0E3E")))
        self.add(String(cx - r_max - 54, cy - 3, "Ground Ops", fontName="Helvetica-Bold", fontSize=8, fillColor=colors.HexColor("#5C0E3E")))
        
        # Value polygon
        p_comm = (cx, cy + r_max * comm / 100.0)
        p_hand = (cx + r_max * hand / 100.0, cy)
        p_sys = (cx, cy - r_max * sys / 100.0)
        p_gro = (cx - r_max * gro / 100.0, cy)
        
        # Shaded area
        self.add(Polygon(
            [p_comm[0], p_comm[1], p_hand[0], p_hand[1], p_sys[0], p_sys[1], p_gro[0], p_gro[1]],
            strokeColor=colors.HexColor("#8D1B3D"),
            strokeWidth=1.5,
            fillColor=colors.Color(141/255.0, 27/255.0, 61/255.0, 0.25)
        ))

def create_progress_bar(percentage, bar_width=120):
    """
    Renders a colored progress bar using nested table grids.
    """
    pct = max(0.0, min(100.0, float(percentage)))
    w1 = max(1.0, bar_width * pct / 100.0)
    w2 = max(1.0, bar_width * (100.0 - pct) / 100.0)
    
    bar_table = Table([["", ""]], colWidths=[w1, w2], rowHeights=[8])
    bar_style = [
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('TOPPADDING', (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
        ('BACKGROUND', (1,0), (1,0), colors.HexColor("#F1F5F9")), # Light Gray Remaining
    ]
    if pct > 0:
        bar_style.append(('BACKGROUND', (0,0), (0,0), colors.HexColor("#8D1B3D"))) # Completed Maroon
    else:
        bar_style.append(('BACKGROUND', (0,0), (0,0), colors.HexColor("#F1F5F9")))
    bar_table.setStyle(TableStyle(bar_style))
    return bar_table

def classify_question(q_id):
    """
    Groups questions dynamically into 4 high-level competency areas.
    """
    if q_id in ["FP1", "FP5", "FP10", "FP12", "FP13", "FP25", "FP32"]:
        return "Communication"
    elif q_id in ["FP6", "FP7", "FP14", "FP15", "FP19", "FP21", "FP23", "FP28", "FP29", "FP30", "FP31"]:
        return "Aircraft Handling"
    elif q_id in ["FP2", "FP3", "FP4", "FP16", "FP17", "FP20", "FP22", "FP24", "FP26", "FP27", "FP33"]:
        return "Systems & Procedures"
    elif q_id in ["FP8", "FP9", "FP18", "FP34"]:
        return "Ground Operations"
    return "Systems & Procedures"

def generate_evaluation_pdf(data):
    """
    Generates a high-fidelity training report PDF.
    """
    buffer = io.BytesIO()
    margin = 54
    doc = SimpleDocTemplate(
        buffer,
        pagesize=letter,
        leftMargin=margin,
        rightMargin=margin,
        topMargin=margin,
        bottomMargin=margin
    )
    
    story = []
    styles = getSampleStyleSheet()
    
    # Palette
    primary_color = colors.HexColor("#5C0E3E")    # Qatar Maroon
    secondary_color = colors.HexColor("#8D1B3D")  # Accent Rose
    border_color = colors.HexColor("#E2E8F0")     # Light gray
    main_text_color = colors.HexColor("#334155")  # Dark slate text
    
    # Load severity configuration
    severity_map = {}
    severity_json_path = os.path.join(settings.BASE_DIR, 'apps', 'checkride', 'data', 'severity_config.json')
    if os.path.exists(severity_json_path):
        try:
            with open(severity_json_path, 'r', encoding='utf-8') as f:
                severity_map = json.load(f)
        except Exception as e:
            print(f"Error loading severity configuration: {e}")
            
    # Load procedure guides mapping (optional guides/photos for wrong answers)
    guides_map = {}
    guides_json_path = os.path.join(settings.BASE_DIR, 'apps', 'checkride', 'data', 'procedure_guides.json')
    if os.path.exists(guides_json_path):
        try:
            with open(guides_json_path, 'r', encoding='utf-8') as f:
                guides_map = json.load(f)
        except Exception as e:
            print(f"Error loading procedure guides JSON: {e}")
            
    # Category Math & Question Grouping
    answers = data.get("answers", [])
    valid_answers = [ans for ans in answers if not ans.get("is_optional", False)]
    
    # Calculate score metrics
    total_checks = len(valid_answers)
    passed_checks = sum(1 for ans in valid_answers if ans.get("is_correct", False))
    failed_checks = total_checks - passed_checks
    pass_rate = (passed_checks / total_checks * 100) if total_checks > 0 else 0
    grade_percentage = pass_rate
    
    # Categorized scores math
    cat_scores = {
        "Communication": {"total": 0, "correct": 0},
        "Aircraft Handling": {"total": 0, "correct": 0},
        "Systems & Procedures": {"total": 0, "correct": 0},
        "Ground Operations": {"total": 0, "correct": 0}
    }
    
    for ans in valid_answers:
        cat = classify_question(ans["id"])
        cat_scores[cat]["total"] += 1
        if ans.get("is_correct", False):
            cat_scores[cat]["correct"] += 1
            
    cat_percentages = {}
    for cat, val in cat_scores.items():
        cat_percentages[cat] = (val["correct"] / val["total"] * 100) if val["total"] > 0 else 100.0

    # Best & Worst categories
    best_cat = max(cat_percentages, key=cat_percentages.get)
    worst_cat = min(cat_percentages, key=cat_percentages.get)
    
    # Extract Critical deficiencies
    critical_failures = []
    minor_failures = []
    for ans in valid_answers:
        if not ans.get("is_correct", False):
            sev = severity_map.get(ans["id"], "minor")
            if sev == "critical":
                critical_failures.append(ans)
            else:
                minor_failures.append(ans)
                
    # Define styles
    title_style = ParagraphStyle('DocTitle', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=20, leading=24, textColor=primary_color)
    subtitle_style = ParagraphStyle('DocSubtitle', parent=styles['Normal'], fontName='Helvetica', fontSize=10, leading=14, textColor=colors.HexColor("#64748B"))
    section_title_style = ParagraphStyle('SecTitle', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=12, leading=16, textColor=primary_color, spaceBefore=10, spaceAfter=6, keepWithNext=True)
    body_style = ParagraphStyle('Body', parent=styles['Normal'], fontName='Helvetica', fontSize=9, leading=13, textColor=main_text_color)
    meta_label = ParagraphStyle('MetaLabel', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=8, leading=10, textColor=colors.HexColor("#64748B"))
    meta_value = ParagraphStyle('MetaValue', parent=styles['Normal'], fontName='Helvetica', fontSize=9, leading=12, textColor=colors.HexColor("#0F172A"))
    card_title_style = ParagraphStyle('CardTitle', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=9, leading=12, textColor=colors.HexColor("#1F2937"))
    card_value_style = ParagraphStyle('CardValue', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=10, leading=13, textColor=primary_color)
    
    # Score band colors & labels
    if grade_percentage >= 90:
        score_band_text = "EXCELLENT"
        score_band_color = colors.HexColor("#15803D")
    elif grade_percentage >= 75:
        score_band_text = "SATISFACTORY"
        score_band_color = colors.HexColor("#0369A1")
    elif grade_percentage >= 60:
        score_band_text = "NEEDS IMPROVEMENT"
        score_band_color = colors.HexColor("#D97706")
    else:
        score_band_text = "RETAKE REQUIRED"
        score_band_color = colors.HexColor("#B91C1C")

    # =========================================================================
    # PAGE 1: EXECUTIVE SUMMARY DASHBOARD
    # =========================================================================
    
    # Header row with logo and doc titles
    logo_path = os.path.join(settings.BASE_DIR.parent, 'assets', 'Qatar_Virtual_logo.PNG')
    titles_col = [
        Paragraph("FLIGHT TRAINING EVALUATION RECORD", title_style),
        Paragraph("PILOT PERFORMANCE scorecard", subtitle_style)
    ]
    if os.path.exists(logo_path):
        try:
            logo_img = Image(logo_path, width=105, height=35)
            header_table = Table([[titles_col, logo_img]], colWidths=[379, 125])
        except Exception:
            header_table = Table([[titles_col, ""]], colWidths=[379, 125])
    else:
        header_table = Table([[titles_col, ""]], colWidths=[379, 125])
        
    header_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ALIGN', (1,0), (1,0), 'RIGHT'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
    ]))
    story.append(header_table)
    story.append(Spacer(1, 12))
    
    # Metadata Block
    meta_cells = [
        [Paragraph("TRAINEE PILOT", meta_label), Paragraph("FLIGHT INSTRUCTOR (FI)", meta_label), Paragraph("EVALUATION DATE", meta_label)],
        [
            Paragraph(f"<b>{data['trainee_name']}</b> ({data['trainee_callsign']})", meta_value),
            Paragraph(f"<b>{data['instructor_name']}</b> ({data.get('instructor_callsign', 'QRV')})", meta_value),
            Paragraph(data['flight_date'], meta_value)
        ]
    ]
    meta_table = Table(meta_cells, colWidths=[175, 175, 154])
    meta_table.setStyle(TableStyle([
        ('BOX', (0,0), (-1,-1), 1, border_color),
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#F8FAFC")),
        ('LEFTPADDING', (0,0), (-1,-1), 8),
        ('RIGHTPADDING', (0,0), (-1,-1), 8),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(meta_table)
    story.append(Spacer(1, 12))
    
    # Dashboard Grid (Score Card & Category Progress & Radar Chart)
    # Scorecard Left
    trend_markup = ""
    if "previous_score" in data:
        try:
            prev = float(data["previous_score"])
            diff = grade_percentage - prev
            if diff >= 0:
                trend_markup = f"<br/><font color='#15803D'>↑ Improved (+{diff:.1f}%)</font>"
            else:
                trend_markup = f"<br/><font color='#B91C1C'>↓ Declined ({diff:.1f}%)</font>"
        except Exception:
            trend_markup = ""
            
    score_p_val = Paragraph(f"<font color='{score_band_color.hexval()}'><b>{grade_percentage:.1f}%</b></font>", ParagraphStyle('SPV', fontName='Helvetica-Bold', fontSize=36, leading=40, alignment=TA_CENTER))
    score_p_lbl = Paragraph(f"<font color='{score_band_color.hexval()}'><b>{score_band_text}</b></font>{trend_markup}", ParagraphStyle('SPL', fontName='Helvetica-Bold', fontSize=10, leading=13, alignment=TA_CENTER))
    
    score_table = Table([[score_p_val], [score_p_lbl]], colWidths=[160])
    score_table.setStyle(TableStyle([
        ('BOX', (0,0), (-1,-1), 1.5, score_band_color),
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#FDFDFD")),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('TOPPADDING', (0,0), (-1,-1), 10),
        ('BOTTOMPADDING', (0,0), (-1,-1), 10),
    ]))
    
    # Progress bars right
    prog_cells = []
    for cat, pct in cat_percentages.items():
        lbl_p = Paragraph(f"<b>{cat}</b>", card_title_style)
        pct_p = Paragraph(f"<b>{pct:.0f}%</b>", ParagraphStyle('CPCT', fontName='Helvetica-Bold', fontSize=9, alignment=TA_RIGHT))
        prog_cells.append([lbl_p, create_progress_bar(pct, 180), pct_p])
        
    prog_table = Table(prog_cells, colWidths=[110, 190, 34])
    prog_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 6),
        ('TOPPADDING', (0,0), (-1,-1), 6),
        ('LINEBELOW', (0,0), (-1,-2), 0.5, border_color),
    ]))
    
    # Nest both columns into a single row table
    dashboard_row_table = Table([[score_table, prog_table]], colWidths=[160, 344])
    dashboard_row_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (0,0), 12),
    ]))
    story.append(dashboard_row_table)
    story.append(Spacer(1, 10))
    
    # Middle row: Radar chart & Metrics summary
    radar_graphic = RadarChart(cat_percentages["Communication"], cat_percentages["Aircraft Handling"], cat_percentages["Systems & Procedures"], cat_percentages["Ground Operations"])
    
    metric_labels = [
        [Paragraph("CHECKLIST STATS", card_title_style), ""],
        [Paragraph("Passed Evaluated Checks", body_style), Paragraph(f"<b>{passed_checks}</b> / {total_checks}", ParagraphStyle('CR', fontName='Helvetica', fontSize=9, alignment=TA_RIGHT))],
        [Paragraph("Deficiencies (Errors)", body_style), Paragraph(f"<b>{failed_checks}</b>", ParagraphStyle('CR', fontName='Helvetica', fontSize=9, alignment=TA_RIGHT))],
        [Paragraph("Critical Deficiencies", body_style), Paragraph(f"<b>{len(critical_failures)}</b>", ParagraphStyle('CR', fontName='Helvetica', fontSize=9, alignment=TA_RIGHT, textColor=colors.HexColor("#B91C1C")))],
        [Paragraph("Best Competency Area", body_style), Paragraph(f"<b>{best_cat}</b>", ParagraphStyle('CR', fontName='Helvetica-Bold', fontSize=8, alignment=TA_RIGHT, textColor=colors.HexColor("#15803D")))],
        [Paragraph("Primary Deficit Area", body_style), Paragraph(f"<b>{worst_cat}</b>", ParagraphStyle('CR', fontName='Helvetica-Bold', fontSize=8, alignment=TA_RIGHT, textColor=colors.HexColor("#B91C1C")))],
    ]
    metric_table = Table(metric_labels, colWidths=[160, 110])
    metric_table.setStyle(TableStyle([
        ('SPAN', (0,0), (1,0)),
        ('LINEBELOW', (0,0), (-1,0), 1, primary_color),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('LINEBELOW', (0,1), (-1,-2), 0.5, border_color),
    ]))
    
    radar_metric_table = Table([[radar_graphic, metric_table]], colWidths=[230, 274])
    radar_metric_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (0,0), 10),
    ]))
    story.append(radar_metric_table)
    story.append(Spacer(1, 10))
    
    # Bottom Row: Critical Items
    crit_list_items = []
    if critical_failures:
        for fail in critical_failures[:5]: # Show top 5 critical items
            crit_list_items.append(Paragraph(f"• <b>{fail['category']}</b>: {fail['selected_option']}", ParagraphStyle('CritI', parent=body_style, textColor=colors.HexColor("#991B1B"))))
    else:
        crit_list_items.append(Paragraph("• No critical procedural deficiencies observed during this flight checkride.", body_style))
        
    crit_box_title = Paragraph("CRITICAL ITEMS REQUIRING REMEDIATION", ParagraphStyle('CritT', fontName='Helvetica-Bold', fontSize=9, leading=12, textColor=colors.white))
    crit_box_table_content = [[crit_box_title]]
    for item in crit_list_items:
        crit_box_table_content.append([item])
        
    crit_box_table = Table(crit_box_table_content, colWidths=[504])
    crit_box_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (0,0), colors.HexColor("#B91C1C")), # Red header
        ('BACKGROUND', (0,1), (0,-1), colors.HexColor("#FEF2F2")), # Light red background
        ('BOX', (0,0), (-1,-1), 1, colors.HexColor("#FCA5A5")),
        ('LEFTPADDING', (0,0), (-1,-1), 10),
        ('RIGHTPADDING', (0,0), (-1,-1), 10),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LINEBELOW', (0,0), (0,0), 1, colors.HexColor("#991B1B")),
    ]))
    
    story.append(crit_box_table)
    story.append(PageBreak())
    
    # =========================================================================
    # PAGE 2: INSTRUCTOR DEBRIEF & STATISTICS
    # =========================================================================
    
    story.append(Paragraph("INSTRUCTOR OVERALL DEBRIEF & PERFORMANCE SUMMARY", section_title_style))
    story.append(Spacer(1, 4))
    
    debrief_text = data.get("general_remarks", "No instructor remarks provided.")
    debrief_p = Paragraph(debrief_text.replace("\n", "<br/>"), ParagraphStyle('DebriefP', parent=body_style, fontSize=10, leading=15))
    
    debrief_card = Table([[debrief_p]], colWidths=[504])
    debrief_card.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (0,0), colors.HexColor("#F8FAFC")),
        ('BOX', (0,0), (0,0), 1, border_color),
        ('LINELEFT', (0,0), (0,0), 4, secondary_color),
        ('TOPPADDING', (0,0), (0,0), 12),
        ('BOTTOMPADDING', (0,0), (0,0), 12),
        ('LEFTPADDING', (0,0), (0,0), 15),
        ('RIGHTPADDING', (0,0), (0,0), 15),
    ]))
    story.append(debrief_card)
    story.append(Spacer(1, 15))
    
    # Competency Insights
    story.append(Paragraph("FLIGHT COMPETENCY DETAILED STATISTICS", section_title_style))
    story.append(Spacer(1, 4))
    
    stat_rows = [
        [Paragraph("COMPETENCY AREA", meta_label), Paragraph("CHECKS EVALUATED", meta_label), Paragraph("SCORE", meta_label), Paragraph("RATING STATUS", meta_label)]
    ]
    for cat, score in cat_percentages.items():
        total_q = cat_scores[cat]["total"]
        if score >= 90:
            status_desc = "<font color='#15803D'><b>EXCELLENT</b></font>"
        elif score >= 75:
            status_desc = "<font color='#0369A1'><b>SATISFACTORY</b></font>"
        elif score >= 60:
            status_desc = "<font color='#D97706'><b>WARNING</b></font>"
        else:
            status_desc = "<font color='#B91C1C'><b>CRITICAL</b></font>"
            
        stat_rows.append([
            Paragraph(f"<b>{cat}</b>", meta_value),
            Paragraph(f"{cat_scores[cat]['correct']} / {total_q}", meta_value),
            Paragraph(f"{score:.1f}%", meta_value),
            Paragraph(status_desc, meta_value)
        ])
        
    stat_table = Table(stat_rows, colWidths=[160, 110, 100, 134])
    stat_table.setStyle(TableStyle([
        ('BOX', (0,0), (-1,-1), 1, border_color),
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#F1F5F9")),
        ('LEFTPADDING', (0,0), (-1,-1), 10),
        ('RIGHTPADDING', (0,0), (-1,-1), 10),
        ('TOPPADDING', (0,0), (-1,-1), 6),
        ('BOTTOMPADDING', (0,0), (-1,-1), 6),
        ('LINEBELOW', (0,0), (-1,-1), 0.5, border_color),
    ]))
    story.append(stat_table)
    story.append(PageBreak())
    
    # =========================================================================
    # PAGE 3+ (DYNAMIC): DETAILED FINDINGS BY FLIGHT PHASE
    # =========================================================================
    
    PHASES = {
        "PRE-FLIGHT": ["FP2", "FP3", "FP4", "FP1"],
        "TAXI": ["FP5", "FP6", "FP7", "FP8", "FP9", "FP10"],
        "TAKEOFF": ["FP11", "FP12", "FP13", "FP14", "FP15", "FP16", "FP17", "FP18", "FP19"],
        "CRUISE": ["FP20", "FP21", "FP22", "FP23"],
        "APPROACH": ["FP24", "FP25", "FP26", "FP27", "FP28"],
        "LANDING": ["FP29", "FP30", "FP31", "FP32", "FP33", "FP34"]
    }
    
    story.append(Paragraph("DETAILED PERFORMANCE FINDINGS BY FLIGHT PHASE", ParagraphStyle('DTitle', fontName='Helvetica-Bold', fontSize=14, leading=18, textColor=primary_color)))
    story.append(Paragraph("Review detailed compliance checks and specific corrective standards below.", subtitle_style))
    story.append(Spacer(1, 8))
    
    answers_map = {ans["id"]: ans for ans in valid_answers}
    
    for phase_name, question_ids in PHASES.items():
        # Filter answers belonging to this phase
        phase_answers = [answers_map[q_id] for q_id in question_ids if q_id in answers_map]
        if not phase_answers:
            continue
            
        phase_story = []
        phase_story.append(Paragraph(phase_name, ParagraphStyle('PhaseHeader', fontName='Helvetica-Bold', fontSize=11, leading=15, textColor=primary_color, spaceBefore=8, spaceAfter=4)))
        
        # Split into passed and failed
        failures = [ans for ans in phase_answers if not ans.get("is_correct", False)]
        passes = [ans for ans in phase_answers if ans.get("is_correct", False)]
        
        # Render failed cards
        for ans in failures:
            sev = severity_map.get(ans["id"], "minor")
            card_status_color = colors.HexColor("#B91C1C") if sev == "critical" else colors.HexColor("#D97706")
            status_lbl_text = f"<font color='{card_status_color.hexval()}'><b>{sev.upper()} DEFICIENCY</b></font>"
            
            hdr_left = Paragraph(f"<b>{ans['category']}</b>", card_title_style)
            hdr_right = Paragraph(status_lbl_text, ParagraphStyle('HRight', fontName='Helvetica-Bold', fontSize=8, alignment=TA_RIGHT))
            
            hdr_table = Table([[hdr_left, hdr_right]], colWidths=[330, 150])
            hdr_table.setStyle(TableStyle([
                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                ('BOTTOMPADDING', (0,0), (-1,-1), 4),
                ('TOPPADDING', (0,0), (-1,-1), 4),
                ('LINEBELOW', (0,0), (-1,-1), 0.5, border_color),
            ]))
            
            card_elements = [hdr_table, Spacer(1, 5)]
            
            # Selected option (Observation)
            selected_text = ans['selected_option']
            if ans.get("is_other", False) and ans.get("other_text"):
                selected_text = f"Other: {ans['other_text']}"
                
            obs_p = Paragraph(f"<b>Issue Observed:</b> {selected_text}", body_style)
            card_elements.append(obs_p)
            card_elements.append(Spacer(1, 3))
            
            # Expected Standard
            std_p = Paragraph(f"<b>Expected Standard:</b> {ans['procedure_details']}", ParagraphStyle('StdP', parent=body_style, textColor=colors.HexColor("#1E3A8A")))
            card_elements.append(std_p)
            
            # Image Rendering if wrong and exists
            q_id = ans.get("id")
            if q_id in guides_map:
                guide_info = guides_map[q_id]
                rel_img_path = guide_info.get("image_path")
                if rel_img_path:
                    abs_img_path = os.path.join(settings.BASE_DIR.parent, rel_img_path)
                    if os.path.exists(abs_img_path):
                        try:
                            card_elements.append(Spacer(1, 6))
                            img_w = guide_info.get("display_width", 380)
                            img_h = guide_info.get("display_height", 160)
                            guide_img = Image(abs_img_path, width=img_w, height=img_h)
                            
                            img_table = Table([[guide_img]], colWidths=[480])
                            img_table.setStyle(TableStyle([
                                ('ALIGN', (0,0), (-1,-1), 'CENTER'),
                                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                                ('LEFTPADDING', (0,0), (-1,-1), 0),
                                ('RIGHTPADDING', (0,0), (-1,-1), 0),
                                ('TOPPADDING', (0,0), (-1,-1), 0),
                                ('BOTTOMPADDING', (0,0), (-1,-1), 0),
                            ]))
                            card_elements.append(img_table)
                            
                            caption_text = guide_info.get("caption")
                            if caption_text:
                                caption_style = ParagraphStyle(
                                    'ImgCaption', 
                                    parent=body_style, 
                                    fontName='Helvetica-Oblique', 
                                    fontSize=7.5, 
                                    textColor=colors.HexColor("#64748b"),
                                    alignment=TA_CENTER
                                )
                                card_elements.append(Spacer(1, 3))
                                card_elements.append(Paragraph(caption_text, caption_style))
                        except Exception:
                            pass
                            
            # Custom remarks
            if ans.get("extra_feedback"):
                card_elements.append(Spacer(1, 4))
                rem_p = Paragraph(f"<b>Instructor Note:</b> <i>{ans['extra_feedback']}</i>", ParagraphStyle('RemP', parent=body_style, textColor=colors.HexColor("#475569")))
                card_elements.append(rem_p)
                
            # Outer card grid
            card_table = Table([[card_elements]], colWidths=[496])
            card_table.setStyle(TableStyle([
                ('BACKGROUND', (0,0), (0,0), colors.white),
                ('BOX', (0,0), (0,0), 1, border_color),
                ('LINELEFT', (0,0), (0,0), 3.5, card_status_color),
                ('TOPPADDING', (0,0), (0,0), 8),
                ('BOTTOMPADDING', (0,0), (0,0), 8),
                ('LEFTPADDING', (0,0), (0,0), 10),
                ('RIGHTPADDING', (0,0), (0,0), 10),
            ]))
            
            phase_story.append(card_table)
            phase_story.append(Spacer(1, 8))
            
        # Render passed items checklist (condensed)
        if passes:
            pass_list = ", ".join([f"<b>{ans['category']}</b>" for ans in passes])
            pass_p = Paragraph(f"<font color='#15803D'><b>✓ Passed:</b></font> {pass_list}", ParagraphStyle('PassList', parent=body_style, fontSize=8.5, leading=12))
            pass_table = Table([[pass_p]], colWidths=[496])
            pass_table.setStyle(TableStyle([
                ('BACKGROUND', (0,0), (0,0), colors.HexColor("#F0FDF4")),
                ('BOX', (0,0), (0,0), 0.5, colors.HexColor("#BBF7D0")),
                ('TOPPADDING', (0,0), (0,0), 6),
                ('BOTTOMPADDING', (0,0), (0,0), 6),
                ('LEFTPADDING', (0,0), (0,0), 10),
                ('RIGHTPADDING', (0,0), (0,0), 10),
            ]))
            phase_story.append(pass_table)
            phase_story.append(Spacer(1, 10))
            
        # Only draw phase if it contains questions
        if len(phase_answers) > 0:
            story.append(KeepTogether(phase_story))
            
    story.append(PageBreak())
    
    # =========================================================================
    # FINAL PAGE: TRAINING RECOMMENDATIONS & SIGN-OFF
    # =========================================================================
    
    story.append(Paragraph("RECOMMENDED TRAINING MODULES & REMEDIATION", section_title_style))
    story.append(Paragraph("The training modules below have been dynamically generated based on checks requiring improvement.", subtitle_style))
    story.append(Spacer(1, 6))
    
    # Smart recommendations map
    recs_triggered = {}
    
    for ans in valid_answers:
        if not ans.get("is_correct", False):
            q_id = ans["id"]
            if q_id in ["FP1", "FP5", "FP10", "FP12", "FP13", "FP25", "FP32"]:
                if "Communication Procedures" not in recs_triggered:
                    recs_triggered["Communication Procedures"] = []
                recs_triggered["Communication Procedures"].append(ans["category"])
            elif q_id in ["FP8", "FP9", "FP18", "FP34", "FP6", "FP7"]:
                if "Ground Control & Taxi" not in recs_triggered:
                    recs_triggered["Ground Control & Taxi"] = []
                recs_triggered["Ground Control & Taxi"].append(ans["category"])
            elif q_id in ["FP2", "FP3", "FP4", "FP16", "FP17", "FP20", "FP24", "FP26", "FP27", "FP33"]:
                if "Aircraft Configuration & Systems" not in recs_triggered:
                    recs_triggered["Aircraft Configuration & Systems"] = []
                recs_triggered["Aircraft Configuration & Systems"].append(ans["category"])
            elif q_id in ["FP14", "FP15", "FP19", "FP21", "FP22", "FP23", "FP28", "FP29", "FP30", "FP31"]:
                if "Flight Stabilization & Profiles" not in recs_triggered:
                    recs_triggered["Flight Stabilization & Profiles"] = []
                recs_triggered["Flight Stabilization & Profiles"].append(ans["category"])
                
    rec_cards = []
    if recs_triggered:
        for idx, (module, sub_topics) in enumerate(recs_triggered.items(), 1):
            bullets = "".join([f"• {topic}<br/>" for topic in set(sub_topics)])
            mod_p = Paragraph(f"<b>{idx}. {module}</b><br/>{bullets}", ParagraphStyle('ModP', parent=body_style, leading=14))
            
            mod_card = Table([[mod_p]], colWidths=[496])
            mod_card.setStyle(TableStyle([
                ('BACKGROUND', (0,0), (0,0), colors.HexColor("#F8FAFC")),
                ('BOX', (0,0), (0,0), 1, border_color),
                ('LINELEFT', (0,0), (0,0), 4.5, primary_color),
                ('TOPPADDING', (0,0), (0,0), 8),
                ('BOTTOMPADDING', (0,0), (0,0), 8),
                ('LEFTPADDING', (0,0), (0,0), 12),
                ('RIGHTPADDING', (0,0), (0,0), 12),
            ]))
            rec_cards.append(mod_card)
            rec_cards.append(Spacer(1, 8))
    else:
        # Perfect flight fallback
        fallback_p = Paragraph("<b>✓ Continuous Professional Development</b><br/>No deficiencies observed during this flight. Trainee is recommended to proceed with standard flight checks and continuous proficiency reviews.", body_style)
        fallback_card = Table([[fallback_p]], colWidths=[496])
        fallback_card.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (0,0), colors.HexColor("#F0FDF4")),
            ('BOX', (0,0), (0,0), 1, colors.HexColor("#BBF7D0")),
            ('LINELEFT', (0,0), (0,0), 4.5, colors.HexColor("#15803D")),
            ('TOPPADDING', (0,0), (0,0), 8),
            ('BOTTOMPADDING', (0,0), (0,0), 8),
            ('LEFTPADDING', (0,0), (0,0), 12),
            ('RIGHTPADDING', (0,0), (0,0), 12),
        ]))
        rec_cards.append(fallback_card)
        
    story.extend(rec_cards)
    story.append(Spacer(1, 15))
    
    # Final sign-off block
    story.append(Paragraph("EVALUATION REVIEW & APPROVAL", section_title_style))
    story.append(Spacer(1, 6))
    
    # Decision block
    status_msg = f"<font color='{score_band_color.hexval()}'><b>{score_band_text}</b></font>"
    desc_p = Paragraph(f"Following this simulator evaluation, the trainee's overall grading stands as {status_msg}.", body_style)
    story.append(desc_p)
    story.append(Spacer(1, 15))
    
    # Signature rows
    sig_cells = [
        ["", "", ""],
        [Paragraph("INSTRUCTOR SIGNATURE", meta_label), Paragraph("TRAINING MANAGER REVIEW", meta_label), Paragraph("DATE", meta_label)],
        ["................................................................", "................................................................", "...................................."]
    ]
    sig_table = Table(sig_cells, colWidths=[180, 180, 144])
    sig_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'BOTTOM'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 2),
        ('TOPPADDING', (0,0), (-1,-1), 2),
        ('LINEBELOW', (0,0), (0,0), 1, colors.HexColor("#CBD5E1")),
        ('LINEBELOW', (1,0), (1,0), 1, colors.HexColor("#CBD5E1")),
        ('LINEBELOW', (2,0), (2,0), 1, colors.HexColor("#CBD5E1")),
        ('TOPPADDING', (0,1), (-1,1), 4),
        ('BOTTOMPADDING', (0,1), (-1,1), 8),
    ]))
    
    story.append(KeepTogether([sig_table]))
    
    # Build Document using NumberedCanvas
    doc.build(story, canvasmaker=NumberedCanvas)
    buffer.seek(0)
    return buffer
