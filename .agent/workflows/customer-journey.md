---
description: Invoice App - Customer (Supplier) Journey Workflow
---

# Customer (Supplier) Journey Workflow

Yeh document batata hai ke jab aik naya Customer (Supplier) signup karta hai, to uske baad use functional taur par kya steps follow karne honge.

## 1. Registration & Email Verification
- **Signup**: Sab se pehle user register karega.
- **Verification**: Signup ke baad user ko aik email verification link bheja jayega. Jab tak email verify nahi hogi, user dashboard access nahi kar sakega.
- **Route**: `/verify-email` (Automatic redirect).

## 2. Onboarding (KYC/KYB)
Email verify karne ke baad, user ko **Onboarding Page** par redirect kiya jayega.
- **Data Submission**: User ko apne business ki details (Company name, Registration, etc.) aur zaroori documents upload karne honge.
- **KYB Status**: Shuru mein status `pending` hoga.
- **Route**: `/onboarding/kyc` (Automatic redirect agar status 'approved' nahi hai).

## 3. Admin Verification
- **Queue**: Admin apne side par `KYB Queue` mein naye suppliers ko dekhega.
- **Approval**: Admin documents review karega aur supplier ko `approved` mark karega.
- **Notification**: Status update hone par supplier ko email notification jayegi.

## 4. Dashboard Access
Approval ke baad, user ko apna **Supplier Dashboard** nazar aayega.
- **Overview**: Yahan wo apni outstanding invoices, revenue, aur overdue amounts dekh sakta hai.
- **Route**: `/supplier/dashboard`.

## 5. Agreement & Legal Signing
Pehli invoice submit karne se pehle, legal agreements zaroori hain.
- **Sign Agreement**: User ko system mein maujood legal terms (Master Agreement) ko digitally sign karna hoga.
- **Validation**: Agar agreement sign nahi kiya, to "Submit Invoice" button use allow nahi karega.
- **Service**: Yeh logic `ContractService` manage karta hai.

## 6. Invoice Submission
Ab user apni invoices finance karwane ke liye submit karega.
- **Form**: Invoice number, Amount, Due Date, aur Buyer selection.
- **Route**: `/invoices/submit`.

## 7. Financing Offer & Funding
- **Review**: Admin invoice check karke pricing decide karega aur Offer issue karega.
- **Acceptance**: Supplier dashboard par offer dekh kar use `Accept` karega.
- **Funding**: Acceptance ke baad funding process start ho jayega aur status `contracted` ya `disbursed` ho jayega.

## 8. Ongoing Communication
- **Chat**: Kisi bhi step par agar koi issue ho, to user **Chat** module ke zariye direct Admin se baat kar sakta hai.
- **Route**: `/chat`.
