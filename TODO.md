# Payment System implementation

# Payment Profile Implementation

## Implement a generic payment profile model for students which has the following fields:
- user_id (foreign key to users table)
- nickname (string)
- currency (string) (EUR/RON)
- observations (text)
- invoice_text (text) - this is the text that will be printed on the invoice and is completed by the admin before creating the first invoice

### Implement the following models now:
- PaymentProfile_Physical_Person
- PaymentProfile_Company

- These models should inherit from the generic payment profile model

- For the PaymentProfile_Physical_Person model have the following information :
* First Name
* Last Name
* Billing address
* Billing city
* Billing state
* Billing zip code
* Billing country

- For the PaymentProfile_Company model have the following information :
* CUI (Romanian tax identification number)
* Company name
* Company trade register number
* Company registration date
* Company legal address
* Billing address
* Billing city
* Billing state
* Billing zip code
* Billing country

### Payment Profile Management

- Implement a controller to manage payment profiles
- Implement a route to manage payment profiles
- Implement a view to manage payment profiles
- Implement a form to create payment profiles
- Implement a form to edit payment profiles
- Implement a form to delete payment profiles
- Implement a form to view payment profiles
- Implement a form to select a payment profile for a user

### Implement the product model changes :
- Product general model : 
  - name (string)
  - description (text)
  - price (decimal) ( always in Euro first)
- Single_Products
    - teacher_assistance (bool)
    - test_id (foreign key to tests table)
- Course_Products
    - number_of_courses (int)
* Admins will then give access to the courses to the students
### Implement the product_acquisition model
- Implement the model with the following fields:
  - payment_profile_id (foreign key to payment_profiles table)
  - product_id (foreign key to products table)
  - eu_platesc_payment
  - acquisition_date (date)
  - acquisition_status (string)
  - acquisition_notes (text)
  - groups_access (foreign key to groups table)
  - tests_access (foreign key to tests table)
  - completion_date (date) - when the student completes the product 
  - is_completed (bool) - whether the student has completed the product
  - invoice_series (string)
  - invoice_number (string)
  ( you can delete the old invoice model)
  # Product Acquisition Management

## Flow 
    * Student selects a product
    * Student selects a payment profile
    * Student pays for the product
    * Student receives access to the product (if it's a course product, the admin will give access to the courses/ If it is a test product Admin will give access to the respective tests)
    * For courses the system is the following :
    For the associated groups with the product, the student will be added to the group and from the presence table we will track the attendance to see how many sessions the student attended to see if they still have access to the product. After completeing all courses the product_acquisition model will be updated to reflect that the student has completed the product. If the product is completed the student will have one week before they be deleted from the group if they do not renew the subscription  ( have an endpoint called renew subscription that will be called by the student to renew the subscription and creates a new product_acquisition record with the same product_id and groups_access but with a new acquisition_date and a new invoice_series and invoice_number) 


    * Admin flow:
    Admin can see all students and what products they bought
    - Admin can manage subscriptions (renew, cancel, etc)
    - Admin can group students based on what products they bought and for students who have an active product 
    - aquisitions and stil have no groups assigned the admin can quickly assign them to a group
    - admin can see all payment profiles for companies and for payment profiles with mentions , invoices need to be manually confirmed for the first time an invoice is created for that payment profile
    
# The payments for the product will be managed with euplatesc and invoice creation with smartbill.
Admins need to have access to what payments are made in the euplatesc account and  when a client pays for a product a new entry is made for the product_acquisition model with the respective product and for the customer (payments are associated automatically with the customer). After the entry is made, if its a first entry and there are mentions in the payment profile for the invoice, then the admin will complete the invoice_text profile field with the respective text and then create  then confirm the creation of the invoice . After this first step, the invoice will be automatically generated and sent to the customer. 
Admins will manage the : payment profiles for each of the customers, will see the list of transactions 
in euplatesc  
