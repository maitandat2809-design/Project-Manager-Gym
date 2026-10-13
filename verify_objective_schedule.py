from docx import Document

document = Document(r"C:\Users\lamtu\Desktop\Final Edit - Objectives 24-Week Schedule Updated.docx")
for paragraph in document.paragraphs:
    if paragraph.text.startswith("Schedule allocation:"):
        print(paragraph.text)
