export default {
    data() {
        return {
            flashMsg: '',
            showFlash: false,
            password: '',
            keywords: Object(),
            error: false,
            loading: false,
            dateStart: '',
            dateEnd: '',
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
                'type': 'keyword_hits',
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
                    this.keywords = json.keywords;
                } else {
                    this.keywords = {};
                    this.flashMsg = json.msg;
                    this.showFlash = true;
                    this.error = true;
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
    <div class="stats_keywords_hits card">   
        <div class="stats_card_content">
            <h1>Keywoord hits</h1>
            <div class=datepickers>
                <input type="date" id="start" v-model="dateStart" :max="dateEnd" />
                <input type="date" id="end" v-model="dateEnd" :max="dateEnd" />
            </div>
            <span class="error" v-if="error">Geen keywoorden gevonden.</span>
            <ul>
                <li v-if="!error"><div><div><b>Keywoord</b></div><div><b>hits</b></div></div></li>
                <li v-for="keyword in keywords" :key="keyword.id"><div><div>{{keyword.keyword}}</div><div>{{keyword.count}}</div></div></li>
            </ul>
        </div>
        <div class="stats_card_refresh"><span class="spinner" v-if="loading"></span><button type="button" v-if="!loading" @click="get_stats()">refresh</button></div>
    </div>`
}

