/**
 * ==================================================
 * سامانه مدیریت دانش فنی و کارگاهی مهندسی عمران و معماری
 * جاوا اسکریپت سراسری و آفلاین (وانیلا جی‌اس - بدون اتصال اینترنتی کلاینت)
 * ==================================================
 */

document.addEventListener("DOMContentLoaded", () => {
    // ۱. مدیریت محدوده آپلود فایل پس‌زمینه (Drag and Drop Zone)
    const uploadZones = document.querySelectorAll(".upload-zone");

    uploadZones.forEach(zone => {
        const fileInput = zone.querySelector("input[type=file]");
        if (!fileInput) return;

        // باز شدن خودکار اکسپلورر ویندوز با کلیک روی محدوده
        zone.addEventListener("click", () => {
            fileInput.click();
        });

        // تغییر استایل در هنگام کشیدن فایل روی زون
        ["dragenter", "dragover"].forEach(eventName => {
            zone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                zone.style.borderColor = "var(--secondary-color)";
                zone.style.backgroundColor = "var(--bg-light)";
            }, false);
        });

        ["dragleave", "drop"].forEach(eventName => {
            zone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                zone.style.borderColor = "var(--primary-color)";
                zone.style.backgroundColor = "#f9fbfd";
            }, false);
        });

        // شنود رها کردن عملیاتی فایل
        zone.addEventListener("drop", (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files; // تخصیص فایل به فیلد اصلی
            updateUploadZoneStatus(zone, files);
        });

        // شنود تغییرات دستی دستی
        fileInput.addEventListener("change", () => {
             updateUploadZoneStatus(zone, fileInput.files);
        });
    });

    function updateUploadZoneStatus(zone, files) {
        const textElement = zone.querySelector("p");
        if (!textElement) return;

        if (files.length === 1) {
            textElement.innerHTML = `<strong>فایل آماده بارگذاری:</strong> ${files[0].name} (${formatBytes(files[0].size)})`;
        } else if (files.length > 1) {
            textElement.innerHTML = `<strong>تعداد ${files.length} فایل آماده برای بارگذاری شد.</strong>`;
        }
    }

    // فرستنده واحد حجم بر اساس بایت استاندارد
    function formatBytes(bytes) {
        if (bytes === 0) return '0 بایت';
        const k = 1024;
        const sizes = ['بایت', 'کلوبایت', 'مگابایت'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // ۲. فعالسازی لایت‌باکس بومی برای گالری کارگاه عمران
    const lightboxTriggers = document.querySelectorAll(".lightbox-trigger");
    if (lightboxTriggers.length > 0) {
        // ایجاد المان گنبدی لایت باکس در بدنه HTML عمومی
        const lightboxModal = document.createElement("div");
        lightboxModal.id = "custom-lightbox";
        lightboxModal.style.position = "fixed";
        lightboxModal.style.top = "0";
        lightboxModal.style.left = "0";
        lightboxModal.style.width = "100%";
        lightboxModal.style.height = "100%";
        lightboxModal.style.backgroundColor = "rgba(0, 0, 0, 0.9)";
        lightboxModal.style.zIndex = "1000";
        lightboxModal.style.display = "none";
        lightboxModal.style.justifyContent = "center";
        lightboxModal.style.alignItems = "center";
        lightboxModal.style.direction = "rtl";

        lightboxModal.innerHTML = `
            <div style="position: relative; max-width: 80%; max-height: 80%; text-align: center;">
                <span id="close-lightbox" style="position: absolute; top: -45px; left: 0; color: #fff; font-size: 30px; cursor: pointer; font-weight: bold;">&times; بستن</span>
                <img id="lightbox-img" src="" style="max-width: 100%; max-height: 80vh; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.5); display: none;">
                <video id="lightbox-video" src="" controls style="max-width: 100%; max-height: 80vh; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.5); display: none;"></video>
                <p id="lightbox-caption" style="color: #fff; margin-top: 15px; font-size: 16px; font-weight: bold; text-shadow: 1px 1px 2px #000;"></p>
            </div>
        `;
        document.body.appendChild(lightboxModal);

        const imgEl = lightboxModal.querySelector("#lightbox-img");
        const videoEl = lightboxModal.querySelector("#lightbox-video");
        const captionEl = lightboxModal.querySelector("#lightbox-caption");

        lightboxTriggers.forEach(trigger => {
            trigger.addEventListener("click", (e) => {
                e.preventDefault();
                const fileUrl = trigger.getAttribute("href");
                const type = trigger.getAttribute("data-type");
                const caption = trigger.getAttribute("data-caption") || "";

                imgEl.style.display = "none";
                videoEl.style.display = "none";
                videoEl.pause();

                if (type === "video") {
                    videoEl.src = fileUrl;
                    videoEl.style.display = "block";
                } else {
                    imgEl.src = fileUrl;
                    imgEl.style.display = "block";
                }

                captionEl.textContent = caption;
                lightboxModal.style.display = "flex";
            });
        });

        // دکمه‌های بستن سینی لایت باکس
        lightboxModal.querySelector("#close-lightbox").addEventListener("click", () => {
            lightboxModal.style.display = "none";
            videoEl.pause();
        });

        lightboxModal.addEventListener("click", (e) => {
            if (e.target === lightboxModal) {
                lightboxModal.style.display = "none";
                videoEl.pause();
            }
        });
    }

    // ۳. شبیه‌ساز تأیید رمز جدید برای کاربران اجباری
    const formReset = document.querySelector("#password-reset-form");
    if (formReset) {
        formReset.addEventListener("submit", (e) => {
            const pass = document.querySelector("#new_password").value;
            const rePass = document.querySelector("#repeat_password").value;

            if (pass !== rePass) {
                e.preventDefault();
                alert("رمز عبور جدید با تکرار آن یکسان نیست!");
            }
        });
    }
});
