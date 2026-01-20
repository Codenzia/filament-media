export class MessageService {
    static showMessage(type, message) {
        FilamentMedia.showNotice(type, message)
    }
}
