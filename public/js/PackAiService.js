// public/js/PackAiService.js
class PackAiService {
    constructor() {
        this.apiKey = 'AIzaSyCqNSzuaDF5mWYiXALJtiRCrBCXzTufoQQ'; // Replace with your actual API key
        this.apiUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent'; // Updated endpoint
        this.currentPacks = [];
    }

    async initialize(packsData) {
        try {
            // Validate input data
            if (!packsData || !Array.isArray(packsData)) {
                throw new Error('Invalid packs data');
            }

            this.currentPacks = packsData.map(pack => {
                if (!pack.id || !pack.nom) {
                    console.warn('Incomplete pack:', pack);
                }
                return {
                    id: pack.id || 0,
                    nom: pack.nom || 'Unknown Name',
                    prix: pack.prix ? `${pack.prix}€` : 'Price not available',
                    description: pack.description?.substring(0, 100) || '...',
                    hebergements: (pack.sejours || []).map(s => ({
                        nom: s.nomHebergement || 'Accommodation',
                        type: s.typeHebergement || ''
                    }))
                };
            });

            console.log('Initialization successful. Packs loaded:', this.currentPacks.length);
            return true;

        } catch (error) {
            console.error('Initialization error:', error);
            throw error;
        }
    }

    async getPackRecommendation(userQuestion) {
        try {
            // Validate input
            if (!userQuestion?.trim()) throw new Error('Empty question');
            if (this.currentPacks.length === 0) throw new Error('No packs available');

            // Construct prompt
            const prompt = `
                Context: Trifly travel assistant
                Language: French exclusively
                Style: Concise (3 sentences max)
                Data: ${JSON.stringify(this.currentPacks.map(p => ({
                    pack: p.nom,
                    prix: p.prix,
                    hebergements: p.hebergements.map(h => h.nom)
                })))}
                Question: "${userQuestion}"
                Example response: "I recommend [pack] at [price] because [criterion]"
            `;

            // API call
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // Fallback timeout for older environments
            const response = await fetch(`${this.apiUrl}?key=${this.apiKey}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contents: [{ parts: [{ text: prompt }] }],
                    generationConfig: {
                        temperature: 0.5,
                        maxOutputTokens: 200
                    }
                }),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error('API error:', errorData);
                throw new Error(`API error ${response.status}: ${errorData.error?.message || 'Unknown error'}`);
            }

            const data = await response.json();
            const aiText = data.candidates?.[0]?.content?.parts?.[0]?.text;

            if (!aiText) throw new Error('Empty API response');

            return {
                text: this.formatResponse(aiText),
                mentionedPackIds: this.extractMentionedPackIds(aiText)
            };

        } catch (error) {
            console.error('getPackRecommendation error:', error);
            return {
                text: this.handleError(error),
                mentionedPackIds: []
            };
        }
    }

    formatResponse(text) {
        if (!text || typeof text !== 'string') return 'Invalid response';
        return text
            .replace(/(\bPack\s+\w+\b)/g, '<strong>$1</strong>')
            .replace(/(\d+\s*€)/g, '<span class="text-success">$1</span>')
            .replace(/\n/g, '<br>');
    }

    extractMentionedPackIds(text) {
        if (!text || typeof text !== 'string') return [];
        return this.currentPacks
            .filter(pack => text.includes(pack.nom))
            .map(pack => pack.id);
    }

    handleError(error) {
        if (error.message.includes('API') || error.message.includes('Erreur')) {
            return `Problème technique: ${error.message}`;
        }
        return 'Veuillez reformuler votre question plus clairement.';
    }
}

export default PackAiService;