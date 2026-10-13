from copy import deepcopy
from pathlib import Path

from docx import Document
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt


SOURCE = Path(r"D:\Project final computing\Project Gym Manager.docx")
OUTPUT = Path(r"C:\xampp\htdocs\Gym Manager\Project Gym Manager - Chapter 10 Updated.docx")


def remove_paragraph(paragraph):
    element = paragraph._element
    element.getparent().remove(element)
    paragraph._p = paragraph._element = None


def insert_after(paragraph, text, style, template):
    new_paragraph = paragraph.insert_paragraph_before()
    paragraph._p.addnext(new_paragraph._p)
    new_paragraph.style = style
    run = new_paragraph.add_run(text)
    if template.runs:
        run.font.name = template.runs[0].font.name
        run.font.size = template.runs[0].font.size
    return new_paragraph


def set_cell_shading(cell, fill):
    tc_pr = cell._tc.get_or_add_tcPr()
    shading = tc_pr.find(qn("w:shd"))
    if shading is None:
        shading = OxmlElement("w:shd")
        tc_pr.append(shading)
    shading.set(qn("w:fill"), fill)


def set_cell_width(cell, inches):
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_w = tc_pr.find(qn("w:tcW"))
    if tc_w is None:
        tc_w = OxmlElement("w:tcW")
        tc_pr.append(tc_w)
    tc_w.set(qn("w:w"), str(int(inches * 1440)))
    tc_w.set(qn("w:type"), "dxa")


def fill_table(table, headers, rows, widths):
    for index, title in enumerate(headers):
        cell = table.cell(0, index)
        cell.text = title
        set_cell_shading(cell, "D9E2F3")
        cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        paragraph = cell.paragraphs[0]
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        for run in paragraph.runs:
            run.bold = True
            run.font.size = Pt(9)
        set_cell_width(cell, widths[index])

    for row_index, values in enumerate(rows, start=1):
        for column_index, value in enumerate(values):
            cell = table.cell(row_index, column_index)
            cell.text = value
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            paragraph = cell.paragraphs[0]
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER if column_index in (1, 2) else WD_ALIGN_PARAGRAPH.LEFT
            for run in paragraph.runs:
                run.font.size = Pt(8.5)
            set_cell_width(cell, widths[column_index])


document = Document(SOURCE)
paragraphs = document.paragraphs
anchor = paragraphs[670]
normal_template = paragraphs[672]
heading2_template = paragraphs[671]
heading3_template = paragraphs[677]

# Start the evaluation chapter on a new page rather than leaving its heading below
# the preceding functional-test table.
anchor.paragraph_format.page_break_before = True

# Remove the previous Chapter 10 material while keeping the chapter heading and Chapter 11.
for paragraph in list(document.paragraphs[671:711]):
    remove_paragraph(paragraph)

last = anchor


def add_normal(text):
    global last
    last = insert_after(last, text, "Normal", normal_template)


def add_h2(text):
    global last
    last = insert_after(last, text, "Heading 2", heading2_template)


def add_h3(text):
    global last
    last = insert_after(last, text, "Heading 3", heading3_template)


def add_table_after_last(headers, rows, widths):
    global last
    table = document.add_table(rows=1 + len(rows), cols=len(headers))
    table.style = "Table Grid"
    table.autofit = False
    fill_table(table, headers, rows, widths)
    last._p.addnext(table._tbl)
    marker = insert_after(last, "", "Normal", normal_template)
    # Move the marker below the table so following paragraphs continue after it.
    table._tbl.addnext(marker._p)
    last = marker


add_h2("10.1 Introduction")
add_normal(
    "This chapter evaluates the usability of the one-to-one training booking process in the Gym Manager Website. "
    "The process enables customers to find a coach, review coach information and available time slots, select one or more suitable sessions, submit a booking request, monitor its status, and manage the booking through viewing, rescheduling, or cancellation."
)
add_normal(
    "The evaluation focuses on whether potential users can complete this booking journey clearly and confidently. "
    "It combines an informal heuristic review with a task-based usability survey. The survey used a Google Forms questionnaire containing participant-profile questions, booking tasks, nine five-point Likert-scale questions, and three open-ended questions."
)
add_normal(
    "The findings indicate a positive initial user experience. Overall satisfaction with the one-to-one booking process received a mean score of 4.80 out of 5, while the combined mean across all valid Likert responses was 4.55 out of 5."
)

add_h2("10.2 Heuristic Evaluation")
add_normal(
    "An informal interface inspection was performed using Jakob Nielsen's ten usability heuristics. "
    "This review complements, rather than replaces, the usability test with users. It identifies whether the booking journey offers understandable feedback, prevents common errors, and gives users adequate control."
)
add_normal("Table 10.1 summarises the heuristic review of the booking process.")

heuristic_rows = [
    ("Visibility of system status", "Booking states such as Pending, Confirmed, and Cancelled are shown in the personal schedule. Email notifications are sent for new bookings, confirmations, rescheduling, and cancellations."),
    ("Match between system and the real world", "The interface uses familiar terms such as Coach, Booking, Time Slot, Schedule, and Booking Status."),
    ("User control and freedom", "Users can view booking details, reschedule a session, cancel a session, and provide a cancellation reason when needed."),
    ("Consistency and standards", "The booking screens use consistent status badges, labels, forms, and navigation patterns."),
    ("Error prevention", "Unavailable, booked, and conflicting slots are identified; the system checks for booking conflicts before a request is submitted."),
    ("Recognition rather than recall", "Coach profiles, available slots, booking status, and personal calendar records are visible to the user."),
    ("Flexibility and efficiency of use", "Users can choose one or multiple time slots and can reschedule an existing session."),
    ("Aesthetic and minimalist design", "The booking journey focuses on essential information: coach, date and time, contact information, and booking status."),
    ("Help users recognise and recover from errors", "Conflict messages direct the user to select another slot, while visible statuses and emails explain what happens after submission."),
    ("Help and documentation", "The system contains the required functions; survey feedback suggests that short guidance for first-time users would further improve discoverability."),
]
add_table_after_last(["Nielsen heuristic", "Evidence in the booking process"], heuristic_rows, [2.15, 4.35])
add_normal(
    "The heuristic review shows that the main booking controls and error-prevention mechanisms are already present. "
    "The main opportunities concern guidance and the visual prominence of existing actions, rather than the absence of core booking functionality."
)

add_h2("10.3 Usability Testing")
# Keep the primary usability-test section together at the top of a fresh page.
last.paragraph_format.page_break_before = True
add_h3("10.3.1 Purpose and Method")
add_normal(
    "The usability test examined whether users could complete the one-to-one training booking process successfully. "
    "Before responding to the questionnaire, participants were asked to: (1) log in or register; (2) view the coach list and select a coach; (3) review coach information and available schedules; (4) select a date and time for a one-to-one session; (5) submit a booking request; (6) view the booking status; and (7) review a confirmation or cancellation notification where available."
)
add_normal(
    "After completing the tasks, participants answered nine quantitative questions on a five-point Likert scale, where 1 represented Strongly Disagree and 5 represented Strongly Agree. Three open-ended questions collected comments on the easiest booking step, any difficulty encountered, and suggestions for improvement."
)

add_h3("10.3.2 Participants")
add_normal(
    "Ten respondents completed the survey. Six respondents (60%) were aged 18-22 and four respondents (40%) were aged 23-30; no respondents were under 18 or above 30. Eight respondents (80%) had previously used an online booking system, while two respondents (20%) had not. All respondents (100%) had previously used online shopping or online payment services."
)
add_normal(
    "These characteristics indicate that the respondents were familiar with online transactions and that most had previous experience with digital booking. The sample therefore provides suitable initial feedback from potential users of the Gym Manager Website. However, the sample size is limited to ten respondents, so the findings should be interpreted as an initial usability evaluation rather than a result that represents all gym customers."
)

add_h3("10.3.3 Hypotheses")
add_normal(
    "H1: Users can find a suitable coach, select an appropriate time slot, and submit a booking request easily. This hypothesis is assessed through the questions on finding the coach list, understanding coach information, selecting a date and time, and submitting a booking request."
)
add_normal(
    "H2: Users can understand, monitor, and manage a booking after submitting a request. This hypothesis is assessed through the questions on conflict notifications, booking status, booking emails, viewing/cancelling/rescheduling a session, and overall satisfaction with the booking process."
)

add_h3("10.3.4 Questionnaire")
add_normal("The quantitative questionnaire contained the following statements:")
for question in [
    "1. I can easily find the coach list on the website.",
    "2. Coach information, including specialty, price, and available schedule, is clear and easy to understand.",
    "3. I can easily select a suitable date and time for a one-to-one training session.",
    "4. The steps for submitting a booking request are simple and easy to follow.",
    "5. The website clearly informs me when a selected time slot is unavailable or conflicts with another booking.",
    "6. I can easily understand my booking status after submitting a booking request.",
    "7. Booking confirmation or cancellation emails help me track my training session conveniently.",
    "8. I can easily view, cancel, or choose another session when necessary.",
    "9. Overall, I am satisfied with the one-to-one training booking process.",
]:
    add_normal(question)
add_normal("The open-ended questions were:")
for question in [
    "1. Which step of the booking process was the easiest to use? Why?",
    "2. Did you experience any difficulty or confusion when finding a coach, selecting a time, or submitting a booking request?",
    "3. What would you suggest to make the one-to-one training booking process clearer and more convenient?",
]:
    add_normal(question)

add_h3("10.3.5 Quantitative Results")
add_normal(
    "Table 10.2 presents the descriptive results. Means were calculated using the valid response count for each statement. "
    "The conflict-notification and booking-status statements received six and eight valid responses respectively; all other statements received ten valid responses."
)

quant_rows = [
    ("Finding the coach list is easy", "10", "4.30", "Users generally found the coach list without difficulty."),
    ("Coach information is clear", "10", "4.50", "Specialty, price, and available schedule were understood clearly."),
    ("Selecting a date and time is easy", "10", "4.50", "Available slots supported the selection of a suitable session."),
    ("Submitting a booking request is simple", "10", "4.40", "The request steps were considered straightforward."),
    ("Conflict or unavailable-slot notification is clear", "6", "4.33", "Valid respondents rated the conflict information positively."),
    ("Booking status is easy to understand", "8", "4.00", "This was the lowest mean but remained positive; status visibility can be refined."),
    ("Booking emails help tracking", "10", "4.40", "Email notifications were considered useful for following a session."),
    ("Viewing, cancelling, or choosing another session is easy", "10", "4.60", "Post-booking management functions were rated highly."),
    ("Overall satisfaction with the booking process", "10", "4.80", "This was the highest-rated statement."),
]
add_table_after_last(["Statement", "Valid n", "Mean / 5", "Interpretation"], quant_rows, [2.35, 0.6, 0.7, 2.85])
add_normal(
    "Across the 82 valid Likert selections, the combined mean was 4.55 out of 5. No response was recorded as Strongly Disagree or Disagree. The distribution was dominated by Agree and Strongly Agree responses, indicating a consistently positive assessment of the booking journey."
)

add_h3("10.3.6 Qualitative Findings")
add_normal(
    "The open-ended responses reinforce the quantitative results. Participants most often described finding a coach, viewing coach information, selecting an available time slot, submitting a request, viewing booking status, and rescheduling as easy parts of the process. Several respondents specifically appreciated the separate display of available, booked, and conflicting slots, as well as the visible Pending, Confirmed, and Cancelled statuses in the personal schedule."
)
add_normal(
    "Six respondents reported no significant difficulty. The remaining comments described minor discoverability or information issues rather than failures of the booking functions: the reschedule button was not immediately noticeable to a first-time user; comparing multiple slots across several days took time; the location for reviewing a submitted booking was not immediately visible; and one respondent wanted clearer confirmation of whether each selected slot created a separate request."
)
add_normal(
    "Suggested improvements were similarly focused on guidance and presentation. Respondents suggested a short instruction explaining that one or more time slots can be selected, clearer emphasis on the coach name and session time in notification emails, a more prominent Reschedule control, time-of-day filters, a View My Bookings button in the success notification, and a pre-submission summary of the coach, selected slots, and number of requests. One respondent considered the current booking process effective and recommended retaining the existing interface and consistent response speed."
)

add_h3("10.3.7 Hypothesis Evaluation")
add_normal(
    "H1 is supported by the descriptive results. The four measures related to finding a coach, understanding coach information, selecting a time, and submitting a request achieved mean scores from 4.30 to 4.50 out of 5, with a combined mean of 4.43 out of 5. These results indicate that respondents could complete the main pre-submission booking steps with little difficulty."
)
add_normal(
    "H2 is also supported. The measures related to booking management and follow-up achieved a combined mean of 4.45 out of 5 across valid responses. Respondents rated email notifications at 4.40 out of 5, viewing/cancelling/choosing another session at 4.60 out of 5, and overall satisfaction at 4.80 out of 5. The booking-status statement scored 4.00 out of 5, suggesting that status communication is effective but remains the most useful area for further interface refinement."
)

add_h3("10.3.8 Conclusion")
add_normal(
    "The usability evaluation indicates that the one-to-one training booking process in the Gym Manager Website is effective and easy to use for the surveyed group. Respondents could locate coaches, understand coach information, select available slots, submit booking requests, and manage bookings after submission. The highest score for overall satisfaction (4.80 out of 5) and the overall valid-response mean (4.55 out of 5) provide positive evidence for the booking design."
)
add_normal(
    "The feedback identifies practical next steps for refinement: make rescheduling more visually prominent, add short guidance for multiple-slot selection, provide a booking summary before submission, make the route to My Bookings more visible after a successful request, and consider time-of-day filtering if the number of coaches or slots increases. These are usability enhancements to an already functioning booking workflow rather than missing core features."
)
add_normal(
    "Because the study included ten respondents, the findings should be treated as an initial evaluation. Future work should include a larger group of gym members and direct observation of users completing booking tasks to validate the results further."
)

document.save(OUTPUT)
print(OUTPUT)
