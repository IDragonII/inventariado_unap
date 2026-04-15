import { createApp } from 'vue'
import { Quasar, Notify } from 'quasar'
import App from './App.vue'
import router from './router'
import store from './stores'

// Import Quasar css
import '@quasar/extras/material-icons/material-icons.css'
import '@quasar/extras/roboto-font/roboto-font.css'
import 'quasar/dist/quasar.css'

const app = createApp(App)

app.use(Quasar, {
    plugins: {
        Notify
    }
})
app.use(router)
app.use(store)

app.mount('#app') 