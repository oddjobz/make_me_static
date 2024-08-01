import { defineStore } from 'pinia'
import { useLogger } from '@/../node_modules/orbit-component-base';

const log               = useLogger()

export const useSubsStore = defineStore('subsStore', {
    state: () => {
        const { nonce, ...mms } = window.MMS_API_Settings
        return {
            model: 'subs',
            mms: mms,
            items: []
        }
    },
    actions: {
        sort (params) {},
        populate (root, callback=null) {
            this.call (root, 'retrieve_products', {}, (response) => {
                if (callback) callback(response)
                this.items = response.items
            })
            return this
        },
        call (root, method, params, callback=null) {
            const socket = this._sockets.get(root)
            if (!socket) throw 'socket not initialised'
            params['uuid'] = this.mms.uuid            
            socket.emit(method, params, (response) => {
                if (!response||!response.ok) log.error('Error calling: ', method, params)
                if (callback) callback(response)
            })
            return this
        },
        sync_call (root, method, params) {
            const socket = this._sockets.get(root)
            if (!socket) throw 'socket not initialised'
            params['uuid'] = this.mms.uuid
            return new Promise((resolve) => {
                socket.emit(method, params, resolve)
            })
        },
    },
    useOrmPlugin: true
})