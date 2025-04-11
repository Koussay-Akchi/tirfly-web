class QrCodeService {
    constructor() {
        this.BASE_URL = "https://api.qrserver.com/v1/create-qr-code/";
        this.SIZE = "150x150";
    }

    /**
     * Génère une URL d’un QR code avec les données passées
     * @param {string} data - Les données à encoder (ex: nom du pack, URL, etc.)
     * @returns {string} - URL de l’image QR code
     */
    generateQrCodeUrl(data) {
        const encodedData = encodeURIComponent(data);
        return `${this.BASE_URL}?size=${this.SIZE}&data=${encodedData}`;
    }

    /**
     * Injecte dynamiquement une image QR code dans un élément cible
     * @param {string} data - Données à encoder
     * @param {HTMLElement} targetElement - Élément DOM dans lequel afficher l'image
     */
    renderQrCode(data, targetElement) {
        const img = document.createElement("img");
        img.src = this.generateQrCodeUrl(data);
        img.alt = "QR Code";
        img.width = 150;
        img.height = 150;
        targetElement.innerHTML = ""; // Clear previous content
        targetElement.appendChild(img);
    }
}

export default QrCodeService;
