// Orbit boilerplace, menu entry for app

import OrbitDir from '@/components/mmsdir.vue';

export function menu (app, router, menu) {
    const version = app.metadata.version
    const description = app.metadata.description
    const host = app.metadata.parameters.host
    app.use(OrbitDir, {
        title: description,
        version: version,
        router: router,
        menu: menu,
        buttons: [{name: 'default', text: '', pri: 50, component: OrbitDir, path: "/", meta: {root: 'mmsdir', host: host}}]
    })
}
