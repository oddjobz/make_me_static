// Our "store" for the "route" table

import { defineStore } from 'pinia'

export const useRouteStore = defineStore('routeStore', {
    state: () => {
        const { nonce, ...mms } = window.MMS_API_Settings
        return {
            model: 'route',
            mms: mms
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
                uuid: this.mms.uuid,
                make_me_static: this.mms
            }
            this.query(params,(response) => {
                // if (!response || !response.ok) throw new Error(response ? response.error : `no query: ${method}`)
                if (callback) callback(response)
            })
            return this
        },
        //
        //  Make sure this user is a Wordpress Admin
        //
        // validate (root, callback=null) {
        //     let params = {
        //         uuid: this.mms.uuid,
        //         url: this.mms.url,
        //         user: this.mms.user,
        //         make_me_static: this.mms
        //     }
        //     const socket = this._sockets.get(root)
        //     if (!socket) throw 'socket not initialised'
        //     socket.emit('validate_connection', params, (response) => {
        //         if (callback) callback(response)
        //     })
        // },
        //
        //  Record that this user has accepted the Terms
        //
        confirm_tacs (root, answer, callback=null) {
            let params = {
                uuid: this.mms.uuid,
                url: this.mms.url,
                answer: answer
            }
            const socket = this._sockets.get(root)
            if (!socket) throw 'socket not initialised'
            params['make_me_static'] = this.mms
            socket.emit('confirm_tacs', params, (response) => {
                if (callback) callback(response)
            })
        },
        //
        //  RPC Call (async)
        //
        call (root, method, params, callback=null) {
            const socket = this._sockets.get(root)
            if (!socket) throw 'socket not initialised'
            socket.emit(method, params, (response) => {
                if (callback) callback(response)
            })
            return this
        },
        //
        //  RPC Call (sync)
        //
        sync_call (root, method, params) {
            const socket = this._sockets.get(root)
            if (!socket) throw 'socket not initialised'
            return new Promise((resolve) => {
                socket.emit(method, params, resolve)
            })
        },

    },
    useOrmPlugin: true
})


// import { defineStore } from 'pinia'

// export const useRouteStore = defineStore('routeStore', {
//     state: () => {
//         const { nonce, ...mms } = window.MMS_API_Settings
//         return {
//             model: 'route',
//             mms: mms
//         }
//     },
//     actions: {
//         sort (params) {},
//         populate (label, callback=null) {
//             let params = {
//                 model: this.model,
//                 label: label,
//                 component: label,
//                 method: `api_${this.model}_get_ids`,
//                 uuid: this.mms.uuid,
//                 make_me_static: this.mms
//             }
//             this.query(params,(response) => {
//                 if (!response || !response.ok) throw new Error(response ? response.error : `no query: ${method}`)
//                 if (callback) callback(response)
//             })
//             return this
//         },
//         call (root, method, params, callback=null) {
//             const socket = this._sockets.get(root)
//             if (!socket) throw 'socket not initialised'
//             socket.emit(method, params, (response) => {
//                 if (callback) callback(response)
//             })
//             return this
//         },
//         sync_call (root, method, params) {
//             const socket = this._sockets.get(root)
//             if (!socket) throw 'socket not initialised'
//             return new Promise((resolve) => {
//                 socket.emit(method, params, resolve)
//             })
//         },
//     },
//     useOrmPlugin: true
// })