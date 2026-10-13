# Short Speaker Notes — Gym Manager Class Diagrams

## 1. Identity and Access

“This diagram presents the common `User` class and the four system roles: Customer, Coach, Gym Manager, and Administrator. The hollow-triangle links show inheritance. `AuthService` authenticates users and works with `PasswordReset` and `EmailService` for registration, login, password reset, and notification emails.”

## 2. Training and Booking

“This module centres on `Booking`. A user books sessions with a coach, while `BookingService` checks conflicts, manages booking status, attendance, and PT-session usage. Booking emails are handled separately by `BookingEmailService`, and users can write coach reviews.”

## 3. Membership and Payment

“A user selects a membership plan and owns membership subscription records. Each subscription is paid through the abstract `Payment` type, implemented by MoMo and COD payment classes. `MembershipService` manages renewal and loyalty benefits, while `PaymentEmailService` sends payment and expiry notifications.”

## 4. Online Shopping

“This diagram models the online store. A user owns a shopping cart, which is composed of cart items. After checkout, an order is composed of order items that reference products. `PaymentService` manages COD and MoMo checkout, and `OrderEmailService` sends confirmation emails.”

## 5. Operations and Reporting

“This module shows the Gym Manager’s operational responsibilities. The manager administers equipment, products, bookings, orders, and subscriptions. `RevenueReport` summarizes booking, order, and membership data, while notification and email services provide operational alerts.”

## UML notation used

- Solid line: association between related classes; labels show the relationship and multiplicity.
- Filled diamond: composition; the part belongs to and is controlled by the whole, for example `Order` and `OrderItem`.
- Dashed arrow: dependency; one service uses another class or service temporarily.
- Solid line with hollow triangle: generalization/inheritance; a subclass inherits from its parent class.
