export default {
    inject: ['userIsLoggedIn'],
    emits: ['login-succes'],
    data() {
        return {
            flashMsg: '',
            showFlash: false,
            password: '',
            loginMessage: '',
            errortje: false
        }
    },

    mounted() {

    },

    methods: {
        async logMeIn() {

            let body = {
                'password': this.password
            }

            let loginPromise = await fetch('./lib/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify(body)
            });

            if(loginPromise.ok) {
                let json = await loginPromise.json();
                if(json.succes) {
                    this.loginMessage = json.msg;
                    this.$emit('login-succes');
                } else {
                    this.flashMsg = json.msg;
                    this.showFlash = true;
                    this.loginMessage = json.msg;
                    this.errortje = true;
                }
            } else {
                console.log("Error! "+loginPromise.status);
            }

        }
    },
    
    template: `
    <flash :msg="flashMsg" @hideflash="this.showFlash=false" v-if="this.showFlash" />
    <div class="login_container" :class="{ error: errortje }">
        <form class="login_form">
            <label for="password">Voer uw wachtwoord in:</label>
            <input id="password" name="password" type="password" placeholder="wachtwoord" v-model="password" autofocus="autofocus" />
            <input type="submit" value="login!" @click.prevent="logMeIn" />
            <span class="login_message">{{ loginMessage }}</span>
        </form>
    </div>`
}

