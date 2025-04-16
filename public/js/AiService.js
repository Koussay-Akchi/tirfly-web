export default class AiService {
    constructor(apiUrl = 'http://localhost:8777/suggestion2') {
        this.apiUrl = apiUrl;
    }

    async getRawSuggestion(pays, description) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                mode: 'no-cors',
                body: JSON.stringify({
                    countries: pays,
                    user_description: description
                })
            });

            //console.log("response : ", response);
            const data = await response.json();
            //console.log("resultat : ", data);
            return data.raw_model_output;
        } catch (error) {
            console.error('Failed to get AI suggestion:', error);
            throw error;
        }
    }
}
