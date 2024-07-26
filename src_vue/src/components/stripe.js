// import stripe from '@/components/stripe.vue'

// export function menu (app, router, menu) {
//     app.use(stripe, {
//         router: router,
//         menu: menu,
//         root: 'stripe',
//         buttons: [
//             {name: 'stripe', text: 'stripe'  , component: stripe , icon: null, pri: 1, path: '/stripe', meta: {root: 'stripe', host: location.host}},
//         ]
//     })
// }
// buttons: [{name: '-', text: '', pri: 50, component: OrbitDir, path: "/", meta: {root: 'mmsdir', host: host}}]
// Orbit boilerplace, menu entry for app

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
