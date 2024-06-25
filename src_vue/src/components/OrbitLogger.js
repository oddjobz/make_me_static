const c_time = 'color:white;font-weight:600;background-color:darkcyan;padding:3px;border-radius:4px;margin-right:6px;padding-left:1em'
const c_debug = 'color:white;font-weight:600;background-color:#333;padding:3px;border-radius:4px;margin-right:4px;padding-left:1em;padding-right:1em'
const c_info = 'color:white;font-weight:600;background-color:slateblue;padding:3px;border-radius:4px;margin-right:4px;padding-left:1em;padding-right:1em'
const c_warn = 'color:black;font-weight:600;background-color:orange;padding:3px;border-radius:4px;margin-right:4px;padding-left:1em;padding-right:1em'
const c_error = 'color:black;font-weight:600;background-color:crimson;padding:3px;border-radius:4px;margin-right:4px;padding-left:1em;padding-right:1em'

class OrbitLogger {

    prefix () {
        return new Date().toLocaleString()
    }
    postfix () {
        const frames = (new Error()).stack.split('\n');
        const index = frames[0].startsWith('Error') ? 3 : 2;
        return frames[index]
    }
    debug (...args) {
        console.debug ('%c%s %c%s', c_time, this.prefix(), c_debug, 'DEBUG', ...args, this.postfix())
    }
    info (...args) {
        console.info ('%c%s %c%s', c_time, this.prefix(), c_info, 'INFO', ...args, this.postfix())
    }
    warn (...args) {
        console.info ('%c%s %c%s', c_time, this.prefix(), c_warn, 'WARNING', ...args, this.postfix())
    }
    error (...args) {
        console.info ('%c%s %c%s', c_time, this.prefix(), c_error, 'ERROR', ...args, this.postfix())
    }
}

function useLogger () {
    return new OrbitLogger()
}

export {
    useLogger
}