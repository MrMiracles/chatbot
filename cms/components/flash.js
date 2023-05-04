export default {
    props: {
        msg: String,
        timeout: {
            type: Number,
            default: 5000 // set to 0 to disable automatic destroy
        },
        warning: {
            type: Boolean,
            default: false
        },
        error: {
            type: Boolean,
            default: false
        },
        success: {
            type: Boolean,
            default: false
        },
        tip: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            timer: Object
        }
    },
    mounted() {
        if(this.timeout != 0) this.timer = setTimeout(this.hide, this.timeout);
    },
    watch: {
        msg() { // when msg changes, reset timer
            clearTimeout(this.timer);
            this.timer = setTimeout(this.hide, this.timeout);
        }
        },
    methods: {
        hide() {
            this.$emit('hideflash');
        }
    },
    
    template: `
    <div class="flash" :class="{ warning: warning, error: error, success: success, tip: tip}" @click="hide()">
        <p>{{msg}}</p>
    </div>   
    `
}

