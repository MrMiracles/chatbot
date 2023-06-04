export default {
    data() {
        return {
            flashMsg: '',
            showFlash: false,
            password: '',
            responses: Object(),
            error: false,
            loading: false,
            dateStart: '',
            dateEnd: '',
            showKeywordsByResponse: false,
            keywordByResponse: Object(),
            keywordByResponseLiked: Object(),
            keywordByResponseDisliked: Object(),
        }
    },

    mounted() {
        // date for datepickers, default 1 month in the past
        let date = new Date();
        this.dateEnd = date.getFullYear()+'-'+(('0'+(date.getMonth()+1)).slice(-2))+'-'+(('0'+date.getDate()).slice(-2));
        date.setMonth(date.getMonth()-1);
        this.dateStart = date.getFullYear()+'-'+(('0'+(date.getMonth()+1)).slice(-2))+'-'+(('0'+date.getDate()).slice(-2));

        this.get_stats();
    },

    methods: {
        async get_stats() {
            this.loading = true;

            let body = {
                'type': 'response_hits',
                'limit': 10,
                'dateStart': this.dateStart,
                'dateEnd': this.dateEnd
            }

            let statsPromise = await fetch('./lib/get_stats.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });

            if(statsPromise.ok) {
                this.loading = false;
                let json = await statsPromise.json();
                if(json.succes) {
                    this.error = false;
                    this.responses = json.responses;
                } else {
                    this.responses = {};
                    this.flashMsg = json.msg;
                    this.showFlash = true;
                    this.error = true;
                }
            } else {
                this.loading = false;
                console.log("Error! "+statsPromise.status);
            }
        }, 
        
        async get_keywords_by_response(id) {
            let body = {
                'type': 'get_keywords_by_response',
                'id': id,
            }

            let statsPromise = await fetch('./lib/get_stats.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });

            if(statsPromise.ok) {
                this.loading = false;
                //console.log(statsPromise.text());
                let json = await statsPromise.json();
                if(json.succes) {
                    this.showKeywordsByResponse = true;
                    this.keywordByResponse = json.keywords;
                    this.keywordByResponseLiked = json.likedKeywords;
                    this.keywordByResponseDisliked = json.dislikedKeywords;
                } else {
                    this.keywordByResponse = {};
                    this.flashMsg = json.msg;
                    this.showFlash = true;
                }
            } else {
                this.loading = false;
                console.log("Error! "+statsPromise.status);
            }
        }
    },

    watch: {
        dateStart() {
            this.get_stats();
        },
        dateEnd() {
            this.get_stats();
        }
    },
    
    template: `
    <Teleport to="body"><flash :msg="flashMsg" @hideflash="this.showFlash=false" v-if="this.showFlash" /></Teleport>
    <Teleport to="body">
        <div class="vuedialog" v-if="showKeywordsByResponse" @click="showKeywordsByResponse=false">   
            <div class="vuedialog-content" @click.stop="">
                <h1>Keywoord welke gelijdde tot antwoord</h1>
                <ul>
                    <li v-if="!error"><div><div><b>Keywoord</b></div><div><b>hits</b></div></div></li>
                    <li v-for="keyword in keywordByResponse" :key="keyword.id"><div><div>{{keyword.keyword}}</div><div>{{keyword.count}}</div></div></li>
                </ul>
                <h2>Keyword geliked bij dit antwoord</h2>
                <span v-if="(keywordByResponseLiked == null)">Geen keywords gevonden.</span>
                <ul>
                    <li v-if="(keywordByResponseLiked != null)"><div><div><b>Keywoord</b></div><div><b>hits</b></div></div></li>
                    <li v-for="keyword in keywordByResponseLiked" :key="keyword.id"><div><div>{{keyword.keyword}}</div><div>{{keyword.count}}</div></div></li>
                </ul>
                <h2>Keyword disliked bij dit antwoord</h2>
                <span v-if="(keywordByResponseDisliked == null)">Geen keywords gevonden.</span>
                <ul>
                <li v-if="(keywordByResponseDisliked != null)"><div><div><b>Keywoord</b></div><div><b>hits</b></div></div></li>
                    <li v-for="keyword in keywordByResponseDisliked" :key="keyword.id"><div><div>{{keyword.keyword}}</div><div>{{keyword.count}}</div></div></li>
                </ul>
                <div class="footer"><button type="button" @click="showKeywordsByResponse=false">sluiten</button></div>
            </div>
        </div>
    </Teleport>
    <div class="stats_responses_hits card">
        <div class="stats_card_content">
            <h1>Antwoord hits</h1>
            <div class=datepickers>
                <input type="date" id="start" v-model="dateStart" :max="dateEnd" />
                <input type="date" id="end" v-model="dateEnd" :max="dateEnd" />
            </div>
            <span  class="error" v-if="error">Geen antwoorden gevonden.</span>
            <ul>
                <li v-if="!error"><div><div><b>Antwoord</b></div><div><b>hits</b></div></div></li>
                <li v-for="response in responses" :key="response.id"><div><div class="stats_clickable" @click="get_keywords_by_response(response.id)" :title="response.response">{{response.response}}</div><div>{{response.count}}</div></div></li>
            </ul>
        </div>
        <div class="stats_card_refresh">
            <span class="tip">Klik op een antwoord voor uitgebreidere statistieken</span>
            <span><span class="spinner" v-if="loading"></span><button type="button" v-if="!loading" @click="get_stats()">refresh</button></span>
        </div>
    </div>`
}

