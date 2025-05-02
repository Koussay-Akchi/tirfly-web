class GeminiClient {
    constructor() {
        this.apiKey = 'AIzaSyCqNSzuaDF5mWYiXALJtiRCrBCXzTufoQQ';
        this.uri = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' + this.apiKey;
    }

    async getSuggestedCity(countries, userDescription) {
        const prompt = `Here are a list of countries: ${countries.join(", ")}\nHere is my idea of a good destination to travel to, pick one of the countries above, answer in a few short sentences along the lines of i think this is the best option and here is why, answer in french: ${userDescription}`;

        const requestBody = {
            contents: [
                {
                    parts: [
                        {
                            text: prompt
                        }
                    ]
                }
            ]
        };

        try {
            const response = await fetch(this.uri, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });

            
            if (!response.ok) {
                throw new Error(`Error fetching suggested city: ${response.statusText}`);
            }

            const data = await response.json();

            
            const suggestedCity = data.candidates?.[0]?.content?.parts?.[0]?.text;

            if (!suggestedCity) {
                throw new Error('Suggested city not found in the response');
            }

            return suggestedCity;
        } catch (error) {
            console.error('Failed to get AI suggestion:', error);
            throw error;
        }
    }
}

export default GeminiClient;