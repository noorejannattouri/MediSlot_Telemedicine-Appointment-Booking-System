# MediSlot_Telemedicine-Appointment-Booking-System
MediSlot is a web platform that lets patients find the right doctor, book a virtual appointment, pay online, and keep every prescription and medical record in one place — while doctors and admins manage the whole process from their own dashboards.

Course: CSE327 — Software Engineering

The problem:
Getting timely medical care is still harder than it should be:

1.No easy way to check doctor availability before showing up or calling around
2.No single place to search doctors by specialty, location, fee, or rating
3.Prescriptions and visit history live on paper, not with the patient or doctor
4.No booking → video-consult → payment flow that works entirely online

MediSlot solves this with one platform covering search, booking, payment, records, and remote consultation.

Features:
1.	Doctor Search & Filtering 
•	Search doctors by specialization, hospital, location, or name. 
•	Filter doctors based on availability, consultation fees, and ratings. 
2.	Online Appointment Booking 
•	View doctors' available time slots. 
•	Book, reschedule, or cancel appointments. 
•	Receive appointment confirmation and reminders. 
3.	Electronic Medical Records 
•	Patients can view their consultation history and prescriptions. 
•	Doctors can access previous visit records to provide better treatment. 

4.	Digital Prescription Management 
•	Doctors can generate electronic prescriptions.
•	Patients can download or print prescriptions for future reference.
•	AI Medicine Assistant (Gemini): Users can search for any medicine or click on a prescribed medicine to get AI-generated information, including its uses, common side effects, dosage guidelines, precautions, and possible drug interactions.
5.	Doctor Dashboard 
•	Manage appointment schedules. 
•	View upcoming consultations. 
•	Update availability and patient records. 
6.	Patient Dashboard 
•	Track upcoming and past appointments. 
•	Access prescriptions and medical history. 
•	Manage personal profile information. 
7.	Payment Integration 
•	Secure online payment for consultation fees. 
•	View payment history and download receipts. 
8.	Admin Dashboard 
•	Manage users (patients and doctors).

Project structure:
medislot/
├── prototype/
│   └── medislot-prototype.html   # standalone frontend prototype (HTML/CSS/JS)
├── backend/                      # PHP application (in progress)
├── database/                     # MySQL schema & seed data (in progress)
├── docs/                         # requirements, UML diagrams, proposal deck
└── README.md
•	Verify doctor registrations. 
•	Monitor appointments and system reports. 
