// Our "store" for the "route" table

import { defineStore } from 'pinia'

export const useRouteStore = defineStore('routeStore', {
    state: () => {
        return {
            model: 'route',
        }
    },
    actions: {
        sort (params) {},
        //
        //  Populate the route table from the server
        //
        populate (label, callback=null) {
            let params = {
                model: this.model,
                label: label,
                component: label,
                method: `api_${this.model}_get_ids`,
                uuid: window.make_me_static.uuid,
                make_me_static: window.make_me_static
            }
            this.query(params,(response) => {
                if (!response || !response.ok) throw new Error(response ? response.error : `no query: ${method}`)
                if (callback) callback(response)
            })
            return this
        },
        //
        //  Make sure this user is a Wordpress Admin
        //
        validate (root, callback=null) {
            let params = {
                uuid: window.make_me_static.uuid,
                url: window.make_me_static.url,
                user: window.make_me_static.user,
                make_me_static: window.make_me_static
            }
            const socket = this._sockets.get(root)
            if (!socket) throw 'socket not initialised'
            socket.emit('validate_connection', params, (response) => {
                if (callback) callback(response)
            })
        },
        //
        //  Record that this user has accepted the Terms
        //
        confirm_tacs (root, answer, callback=null) {
            let params = {
                uuid: window.make_me_static.uuid,
                url: window.make_me_static.url,
                answer: answer
            }
            const socket = this._sockets.get(root)
            if (!socket) throw 'socket not initialised'
            params['make_me_static'] = window.make_me_static
            socket.emit('confirm_tacs', params, (response) => {
                if (callback) callback(response)
            })
        },
    },
    useOrmPlugin: true
})