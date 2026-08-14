# MediSlot_Telemedicine-Appointment-Booking-System
MediSlot is a web platform that lets patients find the right doctor, book a virtual appointment, pay online, and keep every prescription and medical record in one place — while doctors and admins manage the whole process from their own dashboards.

Course: CSE327 — Software Engineering

### The problem:

Getting timely medical care is still harder than it should be:

1.No easy way to check doctor availability before showing up or calling around

2.No single place to search doctors by specialty, location, fee, or rating

3.Prescriptions and visit history live on paper, not with the patient or doctor

4.No booking → video-consult → payment flow that works entirely online

MediSlot solves this with one platform covering search, booking, payment, records, and remote consultation.


## Tech stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, Bootstrap, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Web server | XAMPP |
| AI | Gemini API (Medicine Assistant) |
| Dev tools | VS Code, phpMyAdmin, GitHub |

A classic LAMP-style stack (minus Linux), chosen because it's free, open-source, and already familiar to the whole team from coursework.

## AI Agents

MediSlot uses AI agents to handle tasks that would otherwise require a human doctor, pharmacist, or admin to answer manually.

| Agent | Role |
|---|---|
| **Medicine Assistant** | Powered by Gemini. Patients can search any medicine or click on a prescribed one to get AI-generated info: uses, side effects, dosage guidelines, precautions, and drug interactions. |
| **Doctor Matching Agent** | Suggests the most relevant doctor based on a patient's symptoms, specialty needs, location, and budget, instead of manual filtering alone. |
| **Appointment Reminder Agent** | Sends automated reminders before an upcoming appointment and follow-up nudges after a missed slot. |
| **Triage Assistant** | Asks a patient a few quick questions and recommends the right specialty before they book, so patients don't guess which doctor to see. |


## Project structure

```
medislot/
├── prototype/
│   └── medislot-prototype.html   # standalone frontend prototype (HTML/CSS/JS)
├── backend/                      # PHP application — auth, appointments, payments (in progress)
│   ├── config/                    # database connection & app settings
│   ├── includes/                  # shared PHP — auth check, header/footer, helpers
│   ├── api/                       # endpoints — doctors, appointments, payments, users
│   └── admin/                     # admin-only pages — verify doctors, manage users
├── database/
│   ├── schema.sql                 # table definitions (users, doctors, appointments, prescriptions, payments)
│   └── seed.sql                   # sample/demo data
├── docs/                          # requirements, UML diagrams, proposal deck
└── README.md
```
