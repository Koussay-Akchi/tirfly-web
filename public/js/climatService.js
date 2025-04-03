class ClimatService {
    constructor() {
        this.API_KEY = "P0E+ZkyPDy5kdfsO3/NbTA==g6w2neX15rszBJb1";
        this.API_COORDONNEES = "https://api.api-ninjas.com/v1/geocoding?city=";
        this.API_CLIMAT = "http://climateapi.scottpinkelman.com/api/v1/location/";
        this.API_TEMPERATURE = "https://api.api-ninjas.com/v1/weather";
        this.NOMINATIM_API = "https://nominatim.openstreetmap.org/reverse?format=json&lat=";
    }

    async getCoordonnes(ville) {
        const encodedVille = encodeURIComponent(ville);
        const url = `${this.API_COORDONNEES}${encodedVille}`;
        
        try {
            const response = await fetch(url, {
                method: "GET",
                headers: { "X-Api-Key": this.API_KEY }
            });

            if (!response.ok) {
                console.log("Error fetching data:", response.status);
                return null;
            }

            const jsonResponse = await response.json();
            if (jsonResponse.length === 0) return null;

            const { latitude, longitude } = jsonResponse[0];
            return [latitude, longitude];

        } catch (error) {
            console.error("Error:", error);
            return null;
        }
    }

    async getClimatEnum(latitude, longitude) {
        const url = `${this.API_CLIMAT}${latitude}/${longitude}`;

        try {
            const response = await fetch(url);

            if (!response.ok) {
                console.log("Error fetching climate data:", response.status);
                return null;
            }

            const jsonResponse = await response.json();
            const climateData = jsonResponse.return_values[0];
            const koppenZone = climateData.koppen_geiger_zone;

            switch (koppenZone.charAt(0)) {
                case 'A': return 1;
                case 'B': return 4;
                case 'C': return 2;
                case 'D':
                case 'E': return 3;
                default: return null;
            }

        } catch (error) {
            console.error("Error:", error);
            return null;
        }
    }

    async getTemperature(latitude, longitude) {
        const url = `${this.API_TEMPERATURE}?lat=${latitude}&lon=${longitude}`;

        try {
            const response = await fetch(url, {
                method: "GET",
                headers: { "X-Api-Key": this.API_KEY }
            });

            if (!response.ok) {
                console.log("Error fetching weather data:", response.status);
                return null;
            }

            const jsonResponse = await response.json();
            return jsonResponse.temp || null;

        } catch (error) {
            console.error("Error:", error);
            return null;
        }
    }

    async getVilleEtPays(latitude, longitude) {
        const url = `${this.NOMINATIM_API}${latitude}&lon=${longitude}&zoom=10&addressdetails=1&accept-language=fr`;

        try {
            const response = await fetch(url, {
                method: "GET",
                headers: { "User-Agent": "YourApp/1.0" }
            });

            if (!response.ok) {
                console.log("Error:", response.status);
                return null;
            }

            const jsonResponse = await response.json();
            const address = jsonResponse.address;

            const ville = address.city || address.town || address.village || "";
            const pays = address.country || "";

            return { ville, pays };

        } catch (error) {
            console.error("Error:", error);
            return null;
        }
    }
}

export default ClimatService;
