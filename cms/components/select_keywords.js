export default {
    emits: ['save', 'close'],
    props: ['response'],
    data() {
        return {
            flashMsg: '',
            showFlash: false,
            error: false,
            success: false,
            responseWords: Array(),
        }
    },

    mounted() {
        let response = this.response.response.replace(/(\r\n|\n|\r)/gm, ' %LBR% '); // remove linebreaks
        let responseWords = response.split(' '); // split into single words
        responseWords.forEach(word => {
            if(word.trim() == '') return;
            this.responseWords.push({ 'value': word.trim(), 'selected': false })
        })
    },

    methods: {

        keywordSelected(word) {
            word.selected = (word.selected) ? false : true;

            this.responseWords.forEach(otherword => { // select similar words
                if(otherword.value.toLowerCase().replace(/(\?|\!|\,|\.)/, '') == word.value.toLowerCase().replace(/(\?|\!|\,|\.)/, '')) {
                    otherword.selected = word.selected;
                }
            })
        },

        save() {
            let returnKeywords = Array();
            this.responseWords.forEach(word => {
                if(word.selected) {
                    if(returnKeywords.find((thisword) => { return word.value.toLowerCase().replace(/(\?|\!|\,|\.)/, '') == thisword.toLowerCase().replace(/(\?|\!|\,|\.)/, '') }) == undefined) returnKeywords.push(word.value.replace(/(\?|\!|\,|\.)/, ''));
                }  
            });
            this.$emit('save', this.response, returnKeywords);
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
    <div class="containerResponseWords">
        <div class="selectKeywords">
            <template v-for="word in responseWords">  
                <span class="paragraph" v-if="word.value == '%LBR%'"></span>
                <span v-else class="word" :class="{ selected: word.selected }" @click="keywordSelected(word)">{{ word.value }}</span>
            </template>
        </div>
        <i class="tip">Interpunctie wordt automatisch verwijderd bij het opslaan van de keywoorden.</i>
        <div class="buttons">
            <button type="reset" value="Annuleren" @click="$emit('close')" title="Sluit venster, keywords worden niet opgeslagen">Annuleren</button>
            <button type="submit" value="opslaan" @click.prevent="save">Opslaan</button>
        </div>
    </div>   
    `
}

