import Quill from 'quill'

var BaseImageFormat = Quill.import('formats/image')

const imageFormatAttributesList = [
    'alt',
    'height',
    'width',
    'style'
]

class ImageFormat extends BaseImageFormat {
    static formats(domNode) {
        return imageFormatAttributesList.reduce(function (formats, attribute) {
            if (domNode.hasAttribute(attribute)) {
                formats[attribute] = domNode.getAttribute(attribute)
            }
            return formats
        }, {})
    }
    format(name, value) {
        if (imageFormatAttributesList.indexOf(name) > -1) {
            if (value) {
                this.domNode.setAttribute(name, value)
                return
            }
            this.domNode.removeAttribute(name)
            return
        }
        super.format(name, value)
    }
}

export { ImageFormat }
