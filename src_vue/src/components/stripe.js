import stripe from '@/components/stripe.vue'

export function menu (app, router, menu) {
    const version = app.metadata.version
    const description = app.metadata.description
    const host = app.metadata.parameters.host
    app.use(stripe, {
        title: description,
        version: version,
        router: router,
        menu: menu,
        buttons: [{name: 'stripe', text: '', pri: 50, component: stripe, path: "/stripe", meta: {root: 'stripe', host: host}}]
    })
}
