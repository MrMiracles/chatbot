import selectKeywords from './select_keywords.js';
import papaparse from '../lib/papaparse.min.js';

export default {
    inheritAttrs: false,
    components: {
        selectKeywords
    },
    emits: ['close', 'refreshResponses'],
    data() {
        return {
            flashMsg: '',
            showFlash: false,
            error: false,
            success: false,
            pageSelectFile: true,
            pageSelectResponseHeader: false,
            pageSelectKeywordHeaders: false,
            pageCurrentRow: false,
            showBigExample: false,
            parser: Object(),
            parserComplete: false,
            headers: Array(),
            responseHeader: '',
            currentRow: Object(),
            currentKeywords: '',
            selectKeywordsShow: false
        }
    },
    methods: {

        async addNewResponse(currentResponse) {
            if(this.newKeyword == '') return false;

            let body = {
                'response': currentResponse,
                'keywords': this.currentKeywords
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
                    this.$emit('refreshResponses')
                } else {
                    this.flash(json.msg, true, false);
                }
            } else {
                console.log("Error! "+responsePromise.status);
            }

            this.parser.resume(); // next response
        },

        selectFile() {
            if(this.$refs.filePicker.files.length <= 0) { // bestand geselecteerd?
                this.flash('Selecteer eerst een bestand', true, false);
                return false;
            }

            // go to next page
            this.pageSelectFile = false;
            this.pageSelectResponseHeader = true;

            // clear previous when new file is selected
            this.parser = Object();
            this.headers = Array();
            this.currentRow = Object();
            this.responseHeader = '';

            // parse file
            Papa.parse(this.$refs.filePicker.files[0], {
                header: true,
                skipEmptyLines: true,
                step: (results, parser) => {
                    this.parser = parser;
                    Object.keys(results.data).forEach((key) => {
                        let header = { name: key,  selected: false}
                        this.headers.push(header);
                    });
                    this.currentRow = results.data;
                    parser.pause();
                },
                complete: (results) => {
                    this.pageCurrentRow = false;
                    this.parserComplete = true;
                }
            });
        },

        selectHeader(currentHeader) {
            this.headers.forEach((header) => { // deselect all others
                header.response = false;
            });
            currentHeader.response = true; // select clicked one
            this.responseHeader = currentHeader.name;
        },

        saveSelectedKeywords(response, keywords) {
            this.currentKeywords += ', ' + keywords.join(', ');
            this.selectKeywordsShow = false;
        },


        flash(msg, error, success) {
            this.flashMsg = msg;
            this.success = success;  
            this.error = error;
            this.showFlash = true;
        }
    },
    computed: {
        currentResponse() {
            let returnResponse = Object();
            returnResponse.response = this.currentRow[this.responseHeader];
            let keywords = Array();
            this.headers.forEach((header) => {
                if(header.keyword == true) {
                    keywords.push(this.currentRow[header.name]);
                }
            })
            this.currentKeywords = keywords.join(', ');
            return returnResponse;
        }
    },    
    template: `
    <Teleport to="body"><flash :msg="flashMsg" :error="this.error" :success="this.success" @hideflash="this.showFlash=false" v-if="this.showFlash" /></Teleport>
    <div class="containerImportResponses">
        <div v-if="pageSelectFile" class="pageSelectFile">
            <form @submit.prevent="selectFile()">
                <label for="filePicker">Selecteer een CSV bestand:</label>
                <input id="filePicker" ref="filePicker" type="file" accept=".csv" />
                <div class="helpText">
                    <p>Voorbeeld van opbouw van het CSV bestand:</p>
                    <img src="./style/imgs/importResultsExample.png" width="300" @click="showBigExample=true" style="cursor:pointer" />
                    <Teleport to="body">
                        <div class="vuedialog" v-if="showBigExample==true" @click="showBigExample=false" >
                            <img class="vuedialog-content" src="./style/imgs/importResultsExample.png" />
                        </div>
                    </Teleport>
                </div>
                <div class="buttons">
                    <button @click="$emit('close')">Sluiten</button>
                    <button type="submit">Volgende</button>
                </div>
            </form>
        </div>
        <div v-if="pageSelectResponseHeader" class="pageSelectResponseHeader">
            <label>Selecteer de kolom met antwoorden in onderstaande lijst:</label>
            <ul>
                <li :class="{selected: header.response}" v-for="header in headers" @click="selectHeader(header)">{{header.name}}</li>
            </ul>
            <div class="buttons">
                <button @click="pageSelectResponseHeader=false;pageSelectFile=true">Terug</button>
                <button @click="pageSelectResponseHeader=false;pageSelectKeywordHeaders=true" :disabled="responseHeader==''">Volgende</button>
            </div>
        </div>
        <div v-if="pageSelectKeywordHeaders" class="pageSelectKeywordHeaders">
            <label>Selecteer de kolomen met belangrijke keywoorden uit onderstaande lijst:</label>    
            <ul>
                <template v-for="header in headers">
                    <li :class="{selected: header.keyword}" v-if="header.response==false" @click="header.keyword=(header.keyword)?false:true">{{header.name}}</li>
                </template>
            </ul>
            <div class="buttons">
                <button @click="pageSelectKeywordHeaders=false;pageSelectResponseHeader=true">Volgende</button>
                <button @click="pageSelectKeywordHeaders=false;pageCurrentRow=true">Volgende</button>
            </div>
        </div>
        <div v-if="pageCurrentRow" class="pageCurrentRow">
            <form>    
                <label>Bekijk elke antwoord, klik op 'opslaan' om het antwoord op te slaan of 'overslaan' om dit antwoord over te slaan.</label>
                <div class="formInputFieds">
                    <div>
                        <label>Antwoord:</label>
                        <textarea v-model="currentResponse.response"></textarea>
                    </div>
                    <div>
                        <label>Keywoorden:</label>
                        <textarea v-model="currentKeywords"></textarea>
                    </div>
                </div>
                <div class="newKeywords"><a @click.prevent="selectKeywordsShow=true">Selecteer keywoorden uit de tekst.</a></div>
                <Teleport to="body">
                    <div class="vuedialog" v-if="selectKeywordsShow==true">
                        <div class="vuedialog-content">
                            <selectKeywords :response="currentResponse" @close="selectKeywordsShow=false" @save="saveSelectedKeywords"></selectKeywords>
                        </div>
                    </div>
                </Teleport>
            </form>
            <div class="buttons">
                <button @click="$emit('close')" title="Sluiten, let op: Niet opgeslagen antwoorden gaan verloren!">Sluiten</button>
                <button @click="parser.resume()" title="Sla dit antwoord over">Overslaan</button>
                <button @click="addNewResponse(currentResponse.response)" title="Sla dit anwoord met keywoord op">Opslaan</button>
            </div>
        </div>
        <div v-if="parserComplete" class="pageComplete">
            <p>Klaar! Alle antwoorden zijn verwerkt.</p>
            <button @click="$emit('close')">Sluiten</button>
        </div>
    </div>  
    `
}

