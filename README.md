# FormRelay CF7 | فرم‌رسان

[فارسی](#فارسی) | [English](#english)

## فارسی

**فرم‌رسان** (نام پیشنهادی؛ نام داخلی افزونه فعلاً `cf7-booster` می‌ماند) افزونه‌ای مکمل برای Contact Form 7 است. این پروژه امکانات ثبت پیام‌های فرم، اعلان در تلگرام و بله، قوانین شرطی، صف ارسال و گزارش تحویل را فراهم می‌کند.

### قابلیت‌های موجود در سورس اولیه

- ذخیره پیام‌های ارسالی و خروجی CSV
- ارسال اعلان به تلگرام و بله
- تنظیمات مقصد و قالب پیام برای هر فرم
- قوانین خودکارسازی با شرط‌های AND و OR
- صف ارسال با WP-Cron، تلاش مجدد و ثبت گزارش

### وضعیت توسعه

**این شاخه (`staging`) برای بازبینی و تست است، نه انتشار نهایی.**
پیاده‌سازی فعلی نیازمند رفع مشکلات احتمالی ارسال تکراری، مدیریت تلاش مجدد، کنترل وضعیت تحویل و بازبینی امنیت و ترجمه‌ها است. قبل از استفاده در محیط اصلی، نصب و ارسال آزمایشی فرم در یک سایت تست لازم است.

### نصب و تست

1. افزونه Contact Form 7 را در وردپرس نصب و فعال کنید.
2. این افزونه را جداگانه نصب و فعال کنید.
3. ربات و مقصد تلگرام یا بله را در تنظیمات افزونه مشخص کنید.
4. یک فرم آزمایشی ارسال کنید و ذخیره فرم، اعلان، صف ارسال و گزارش‌ها را بررسی کنید.
5. خطاهای API، ارسال مجدد، حالت فارسی/انگلیسی و نمایش راست‌به‌چپ را هم آزمایش کنید.

> **حریم خصوصی:** اطلاعات فرم‌ها می‌تواند شامل داده‌های شخصی باشد؛ دسترسی مدیران و مدت نگهداری داده‌ها را محدود کنید. توکن‌های ربات یا اطلاعات واقعی کاربران را وارد Git نکنید.

## English

**FormRelay CF7** (proposed display name; the existing `cf7-booster` plugin slug is retained for compatibility) extends Contact Form 7 with submission storage, Telegram and Bale notifications, per-form settings, conditional routing, delivery queue, retry handling, logs, and CSV export.

### Development status

**The `staging` branch is intended for review and testing, not production deployment.** The imported code requires functional, security, localization, and reliability checks before release.

### Installation and smoke tests

1. Install and activate Contact Form 7 first.
2. Install this plugin as a separate WordPress plugin.
3. Configure channel tokens and recipient destinations.
4. Submit a test form and check its stored submission, notification delivery, retries and logs.
5. Test invalid credentials, API failures, Persian/English localization and RTL layout.

Do not commit bot credentials or real customer information. Merge into `main` only after manual approval.

## Staging 1.2.0-beta2 / نسخه آزمایشی

شاخه staging دارای داشبورد بازطراحی‌شده، تنظیمات صف و حریم خصوصی، توقف ارسال اعلان‌ها، حذف اختیاری اطلاعات قدیمی، و پیام آزمایشی تلگرام/بله است. پاک‌سازی داده‌ها در حالت پیش‌فرض غیرفعال است. برای تست روی وردپرس از بسته ساخته‌شده توسط GitHub Actions استفاده کنید؛ جزئیات در TESTING.md آمده‌اند.

The staging branch provides the redesigned dashboard, validated engine/privacy settings, global pause, opt-in retention, and a real sample-delivery test for Telegram and Bale. Retention is off by default. The GitHub Actions package job builds an installable plugin ZIP after tests pass.
