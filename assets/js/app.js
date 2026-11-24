/* FrostMart — interaksi UI tanpa library eksternal */
(() => {
    "use strict";

    const body = document.body;
    const sidebar = document.querySelector("[data-sidebar]");
    const menuButton = document.querySelector("[data-sidebar-toggle]");
    const closeButton = document.querySelector("[data-sidebar-close]");
    const overlay = document.querySelector("[data-sidebar-overlay]");

    const setSidebar = (open) => {
        if (!sidebar) return;
        body.classList.toggle("sidebar-open", open);
        menuButton?.setAttribute("aria-expanded", open ? "true" : "false");
        if (open) {
            closeButton?.focus({ preventScroll: true });
        } else if (document.activeElement === closeButton) {
            menuButton?.focus({ preventScroll: true });
        }
    };

    menuButton?.addEventListener("click", () => setSidebar(true));
    closeButton?.addEventListener("click", () => setSidebar(false));
    overlay?.addEventListener("click", () => setSidebar(false));

    window.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && body.classList.contains("sidebar-open")) {
            setSidebar(false);
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 980 && body.classList.contains("sidebar-open")) {
            setSidebar(false);
        }
    });

    /* --------------------------------------------------------
       Notifikasi
       -------------------------------------------------------- */
    document.querySelectorAll("[data-toast]").forEach((toast) => {
        let timer = null;

        const dismiss = () => {
            if (!toast.isConnected || toast.classList.contains("is-leaving")) return;
            toast.classList.add("is-leaving");
            window.setTimeout(() => toast.remove(), 260);
        };

        toast.querySelector("[data-toast-close]")?.addEventListener("click", dismiss);
        timer = window.setTimeout(dismiss, 5200);

        toast.addEventListener("mouseenter", () => window.clearTimeout(timer));
        toast.addEventListener("mouseleave", () => {
            timer = window.setTimeout(dismiss, 2400);
        });

        toast.addEventListener("transitionend", () => window.clearTimeout(timer), { once: true });
    });

    /* --------------------------------------------------------
       Dialog konfirmasi hapus
       -------------------------------------------------------- */
    const dialog = document.getElementById("confirmDialog");
    const messageNode = dialog?.querySelector("[data-confirm-message]");
    const confirmButton = dialog?.querySelector("[data-confirm-ok]");
    const cancelButton = dialog?.querySelector("[data-confirm-cancel]");
    let pendingForm = null;

    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            event.preventDefault();

            if (!dialog || typeof dialog.showModal !== "function") {
                if (window.confirm(form.dataset.confirm || "Hapus data ini?")) {
                    form.submit();
                }
                return;
            }

            pendingForm = form;
            if (messageNode) {
                messageNode.textContent = form.dataset.confirm || "Tindakan ini tidak bisa dibatalkan.";
            }
            dialog.showModal();
        });
    });

    cancelButton?.addEventListener("click", () => dialog?.close());

    confirmButton?.addEventListener("click", () => {
        if (!pendingForm) {
            dialog?.close();
            return;
        }

        const form = pendingForm;
        pendingForm = null;
        dialog?.close();
        form.submit();
    });

    dialog?.addEventListener("close", () => {
        pendingForm = null;
    });

    dialog?.addEventListener("click", (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    /* --------------------------------------------------------
       Format angka ribuan pada input harga
       -------------------------------------------------------- */
    const formatThousands = (value) => {
        const digits = value.replace(/\D/g, "");
        return digits === "" ? "" : digits.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    };

    document.querySelectorAll('input[inputmode="numeric"]').forEach((input) => {
        input.addEventListener("blur", () => {
            input.value = formatThousands(input.value);
        });
    });

    /* --------------------------------------------------------
       Cegah klik ganda saat menyimpan form biasa
       -------------------------------------------------------- */
    document.querySelectorAll("form").forEach((form) => {
        if (form.hasAttribute("data-confirm")) return;
        form.addEventListener("submit", () => {
            const submitter = form.querySelector('button[type="submit"]');
            if (!submitter) return;
            window.setTimeout(() => {
                submitter.disabled = true;
                submitter.classList.add("is-loading");
            }, 0);
        });
    });
})();
