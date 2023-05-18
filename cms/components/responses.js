import selectKeywords from './select_keywords.js';
import importResponses from './import_responses_csv.js';

export default {
    inject: ['userIsLoggedIn'],
    inheritAttrs: false,
    components: {
        selectKeywords, importResponses
    },
    emits: ['refreshKeywords'],
    props: {
        filterByKeywords: {
            type: Array,
            default: Array()
        }
    },
    data() {
        return {
            flashMsg: '',
            showFlash: false,
            error: false,
            success: false,
            filterKeywords: this.filterByKeywords,
            keywords: Array,
            responses: Array,
            newResponse: '',
            newResponseKeywords: '',
            importResponseShow: false
        }
    },

    mounted() {
        this.getKeywords();
        this.getResponses();
    },

    methods: {
        async getKeywords() {
            // geef alle keywords terug
            let keywordsPromise = await fetch('./lib/get_keywords.php');
            if(keywordsPromise.ok) {
                let json = await keywordsPromise.json();
                if(json.succes) {
                    this.keywords = json.keywords;
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+keywordsPromise.status);
            }
        },

        async getResponses() {           
            // get responses by filtered keywords
            if(this.filterKeywords.length > 0) { 
                let body = {
                    'keywords': this.filterKeywords
                }
                let responsePromise = await fetch('./lib/get_responses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json;charset=utf-8'
                    },
                    body: JSON.stringify(body)
                });
                if(responsePromise.ok) {
                    let json = await responsePromise.json();
                    if(json.succes) {
                        this.responses = json.responses;
                    } else {
                        this.responses = Array();
                        this.flash(json.msg, true, false);
                    }
                } else {
                    console.log("Error! "+responsePromise.status);
                }
            } else {
                // geef alle keywords terug
                let responsePromise = await fetch('./lib/get_responses.php');
                if(responsePromise.ok) {
                    let json = await responsePromise.json();
                    if(json.succes) {
                        this.responses = json.responses;
                    } else {
                        this.flash(json.msg, true, false);
                    }
                } else {
                    console.log("Error! "+responsePromise.status);
                }
            }

            
        },

        async addNewResponse() {
            if(this.newKeyword == '') return false;

            let body = {
                'response': this.newResponse,
                'keywords': this.newResponseKeywords
            }

            let responsePromise = await fetch('./lib/add_response.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });
            if(responsePromise.ok) {
                let json = await responsePromise.json();
                if(json.succes) {
                    this.flash(json.msg, false, true);
                    this.newResponse = '';
                    this.newResponseKeywords = '';
                    this.getResponses();
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+responsePromise.status);
            }
        },

        async editResponse(response) {
            response.edit.close();
        
            let body = {
                'id': response.id,
                'response': response.newResponse
            }

            let updatePromise = await fetch('./lib/update_response.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });
            if(updatePromise.ok) {
                let json = await updatePromise.json();
                if(json.succes) {
                    this.flash(json.msg, false, true);
                    this.getResponses();
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+updatePromise.status);
            }
            
        },

        async deleteResponse(id) {
            if (!confirm("Weet je zeker dat je dit antwoord wilt verwijderen?")) return false; // laatste waarschuwing
            
            let body = {
                'id': id
            }

            let keywordsPromise = await fetch('./lib/delete_response.php', {
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
                    this.getResponses();
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+keywordsPromise.status);
            }
        },

        async linkConnection(response) {
            let body = {
                'respid': response.id,
                'keyword': response.newKeyword
            }
            let linkPromise = await fetch('./lib/link_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });
            if(linkPromise.ok) {
                let json = await linkPromise.json();
                if(json.succes) {
                    this.flash(json.msg, false, true);
                    this.getResponses();
                    this.getKeywords();
                    this.$emit('refreshKeywords');
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+linkPromise.status);
            }
        },

        async unlinkConnection(respid, keyid) {
            let body = {
                'respid': respid,
                'keyid': keyid
            }

            let unlinkPromise = await fetch('./lib/unlink_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });
            if(unlinkPromise.ok) {
                let json = await unlinkPromise.json();
                if(json.succes) {
                    this.flash(json.msg, false, true);
                    this.getResponses();
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+unlinkPromise.status);
            }
        },

        async saveSelectedKeywords(response, keywords) {
            response.selectKeywordsShow=false; // close dialog

            let body = {
                'respid': response.id,
                'keyword': keywords
            }
            let saveSelectedPromise = await fetch('./lib/link_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });
            if(saveSelectedPromise.ok) {
                let json = await saveSelectedPromise.json();
                if(json.succes) {
                    this.flash(json.msg, false, true);
                    this.getResponses();
                    this.getKeywords();
                    this.$emit('refreshKeywords');
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+saveSelectedPromise.status);
            }
        },

        flash(msg, error, success) {
            this.flashMsg = msg;
            this.success = success;  
            this.error = error;
            this.showFlash = true;
        }
    },
    watch: {
        filterByKeywords: {
            deep: true,
            handler() {
                this.filterKeywords = this.filterByKeywords;
                this.getResponses();
            }
        }
    },    
    template: `
    <flash :msg="flashMsg" :error="this.error" :success="this.success" @hideflash="this.showFlash=false" v-if="this.showFlash" />
    
    <Teleport to="body">
        <div class="vuedialog" v-if="importResponseShow==true">
            <div class="vuedialog-content">
                <importResponses @close="importResponseShow=false" @refreshResponses="getResponses()"></importResponses>
            </div>
        </div>
    </Teleport>
    <div class="containerResponses">

        <h1>Antwoorden</h1>

        <div class="addResponse">
            <form @submit.prevent="addNewResponse()">
                <div><label>Voeg antwoord toe:</label><a @click="importResponseShow=true">Importeer antwoorden</a></div>
                <textarea name="response" v-model="newResponse" placeholder="Typ hier een antwoord."></textarea>
                <textarea name="keywords" v-model="newResponseKeywords" placeholder="Typ hier keywords die verbonden moeten worden met het antwoord. Scheid de keywoorden met komma's."></textarea>
                <input type="submit" value="Toevoegen">
            </form>
        </div>

        <datalist id="keywords">
            <option v-for="keyword in keywords">{{keyword.keyword}}</option>
        </datalist>

        <div class="responseList">
            <ul>
                <li v-for="response in responses" class="response">
                    <div>
                        <b id="titel22" :class="(response.togglelong) ? 'responseTextLong' : 'responseText'" @click="response.togglelong = (response.togglelong ? false : true)">{{response.response}}</b> 
                        <div class="actionButtons">
                            <img src="style/icons/edit.png" @click.prevent="response.newResponse=response.response; response.edit.showModal()" width="16" height="16" title="Antwoord bewerken">
                            <img src="style/icons/delete.png" @click.prevent="deleteResponse(response.id)" width="16" height="16" title="Antwoord verwijderen">
                        </div>
                        
                        <dialog class="editdialog" :ref="(el) => { response.edit = el }">
                            <form @submit.prevent="editResponse(response)">
                                <textarea v-model="response.newResponse"></textarea>
                                <div>
                                    <button value="Annuleren" @click.prevent="response.edit.close()" title="Sluit venster, alle wijziging worden ongedaan gemaakt">Annuleren</button>
                                    <button type="submit" value="opslaan">Opslaan</button>
                                </div>
                            </form>
                        </dialog>
                    </div>
                    <div>
                        <ul>
                            <li v-for="keyword in response.keywords">
                                {{keyword.keyword}} <img src="style/icons/unlink.png"  @click.prevent="unlinkConnection(response.id, keyword.id)" style="vertical-align: -10%; cursor: pointer" width="16" height="16" title="Verbinding verwijderen">
                            </li>
                        </ul>
                        <div class="newKeywords"><a @click.prevent="response.selectKeywordsShow=true">Selecteer keywoorden uit de tekst</a> of voeg een nieuw keywoord toe:</div>
                        <form @submit.prevent="linkConnection(response)">
                            <input name="keyword" v-model="response.newKeyword" type="text" autocomplete="off" list="keywords" placeholder="Typ keywoord.">
                            <input type="submit" value="Verbinden!"> <i class="tip">(als het keywoord niet bestaat wordt deze toegevoegd)</i>
                        </form>
                    </div>
                    <Teleport to="body">
                    <div class="vuedialog" v-if="response.selectKeywordsShow==true">
                        <div class="vuedialog-content">
                            <selectKeywords :response="response" @close="response.selectKeywordsShow=false" @save="saveSelectedKeywords"></selectKeywords>
                        </div>
                    </div>
                    </Teleport>
                </li>
            </ul>
        </div>
    </div>  
    `
}

