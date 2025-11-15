# 🏨 Automatic Payment Reminder System

## Ano ang ginawa natin:

### ✅ 1. Database Tables
- **notifications** - para sa mga reminders at alerts
- **payment_history** - para sa payment records
- **reservations** - dinagdag ang mga columns:
  - `stay_start_date` - kailan nag-start ang stay
  - `stay_end_date` - kailan mag-eend (optional)
  - `monthly_due_date` - kailan due ang payment
  - `last_payment_date` - last payment date

### ✅ 2. Automatic Features

#### Pag-approve ng reservation:
- **Auto-set ng start date** sa today
- **Auto-calculate ng due date** (30 days from start)
- **Auto-create ng welcome notification**

#### Daily Payment Reminders:
- **3 days before due date** - mag-sesend ng reminder
- **After due date** - mag-sesend ng overdue notice
- **Notifications para sa admin at user**

#### Payment Processing:
- **Auto-update ng next due date** (+30 days)
- **Record sa payment history**
- **Confirmation notification**

### ✅ 3. Admin Dashboard
- **Payment alerts widget** - makikita ang mga overdue at due soon
- **Real-time notifications** - may button para mag-check
- **Quick links** sa reports

### ✅ 4. Reports System
- **Enhanced tracking** - with real stay dates
- **Payment status** - accurate based on due dates
- **Overdue detection** - automatic

## Paano gamitin:

### Para sa Daily Reminders:
```bash
# Run ito every day (pwede sa Windows Task Scheduler)
php C:\wamp\www\lasttry\payment_reminders.php
```

### Para sa Manual Check:
1. Go sa **Dashboard**
2. Click **"Check for New Alerts"**
3. Makikita mo yung mga bagong reminders

### Para sa Payment Recording:
1. Go sa **Admin Reports**
2. Click **"Record Payment"** sa tenant
3. **Auto-update ng due date** (+30 days)
4. **Notification sa user**

## Benefits:

### ✨ Para sa Admin:
- **Hindi na kailangan mag-manual track** ng payments
- **Automatic alerts** para sa overdue
- **Clear dashboard** ng payment status
- **Organized payment history**

### ✨ Para sa Users:
- **3-day advance reminder** - hindi na makakalimutan
- **Clear payment dates** - alam kung kelan due
- **Payment confirmations** - receipt via notification

### ✨ Para sa System:
- **Accurate tracking** ng stay periods
- **Automatic calculations** - walang manual errors
- **Complete audit trail** - lahat naka-record

## Setup sa Production:

1. **Windows Task Scheduler** (for daily reminders):
   - Task: `php C:\wamp\www\lasttry\payment_reminders.php`
   - Schedule: Daily at 9:00 AM

2. **Admin Training**:
   - Check dashboard daily
   - Use reports for payment recording
   - Monitor overdue accounts

3. **User Communication**:
   - Inform tenants about automatic reminders
   - Teach them to check notifications
   - Set clear payment policies

Ang ganda nito kasi **everything is automated** na! Hindi na kailangan mag-manual track ng dates at payments. 🎉