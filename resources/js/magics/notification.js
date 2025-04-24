export default (userOptions) => {
    const options = {
        variant: userOptions.variant || 'success',
        duration: userOptions.duration || 5000,
        text: userOptions.text || 'This is a message',
    }

    const variantMethods = {
        error: 'danger',
        success: 'success',
        info: 'info',
        warning: 'warning'
    }

    const notification = new FilamentNotification()
        .title(options.text)
        .duration(options.duration)

    const method = variantMethods[options.variant] || 'success'

    notification[method]().send()
}
