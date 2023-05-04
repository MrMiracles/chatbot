export default {
    inject: ['userIsLoggedIn'],
    emits: ['filterByKeyword'],
    data() {
        return {
            flashMsg: '',
            showFlash: false,
            error: false,
            success: false,
            newKeyword: '',
            searchValue: '',
            allKeywords: Array(),
            keywords: Array(),
            filterKeywords: Array()
        }
    },

    mounted() {
        this.getKeywords();
    },

    methods: {
        async getKeywords() {
            // geef alle keywords terug
            let keywordsPromise = await fetch('./lib/get_keywords.php');
            if(keywordsPromise.ok) {
                let json = await keywordsPromise.json();
                if(json.succes) {
                    this.allKeywords = json.keywords;
                    this.keywords = this.allKeywords;
                    this.searchKeywords();
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+keywordsPromise.status);
            }
        },

        searchKeywords() {
            if(this.searchValue.length == 0) { // laat alle keywords zien
                this.keywords = this.allKeywords;
            } else { // zoeken in keywords
                this.keywords = this.allKeywords.filter((el) => el.keyword.toLowerCase().includes(this.searchValue.toLowerCase()));
            }  
        },

        async addNewKeyword() {
            if(this.newKeyword == '') return false;

            let body = {
                'keyword': this.newKeyword
            }

            let keywordsPromise = await fetch('./lib/add_keyword.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });
            if(keywordsPromise.ok) {
                let json = await keywordsPromise.json();
                if(json.succes) {
                    this.flash('Keywoord "'+this.newKeyword+'" toegevoegd!', false, true);
                    this.newKeyword = '';
                    this.getKeywords();
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+keywordsPromise.status);
            }
        },

        async deleteKeyword(id) {
            let body = {
                'id': id
            }

            let keywordsPromise = await fetch('./lib/delete_keyword.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });
            if(keywordsPromise.ok) {
                let json = await keywordsPromise.json();
                if(json.succes) {
                    this.flash(json.msg, false, true);
                    this.getKeywords();
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+keywordsPromise.status);
            }
        },

        filterResponsesByKeyword(keyword) {
            this.$emit('filterByKeyword', keyword.keyword);
            keyword.selected=(keyword.selected) ? false : true;
        },

        flash(msg, error, success) {
            this.flashMsg = msg;
            this.success = success;  
            this.error = error;
            this.showFlash = true;
        }
    },
    
    template: `
    <flash :msg="flashMsg" :error="this.error" :success="this.success" @hideflash="this.showFlash=false" v-if="this.showFlash" />
    <div class="containerKeywords">

        <h1>Keywords</h1>

        <div class="addKeyword">
            <form>
                <label>Voeg keywoord toe: </label>
                <input type="text" v-model="newKeyword" placeholder="Vul nieuw keywoord in">
                <input type="submit" value="Toevoegen" @click.prevent="addNewKeyword()">
            </form>
        </div>
        <div class="searchKeyword">
            <input type="text" v-model="searchValue" @keyup="searchKeywords()" placeholder="Type zoekwoord">
        </div>

        <div class="keywordList">
            <ul>
                <li class="card small" :class="{selected : keyword.selected}" v-for="keyword in keywords" :key="keyword.id" @click.self="filterResponsesByKeyword(keyword)">{{keyword.keyword}} 
                <img src="style/icons/filtertby.png" title="Gefiltert op dit keywoord" width="16" height="16" v-if="keyword.selected">
                <img class="delete" src="style/icons/delete.png" @click.prevent.stop="deleteKeyword(keyword.id)" title="Keywoord verwijderen" width="16" height="16">   
                </li>
            </ul>
        </div>
    </div>   
    `
}

