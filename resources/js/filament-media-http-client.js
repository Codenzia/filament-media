import $ from 'jquery'

export class HttpClient {
    constructor() {
        this.configs = {}
    }

    make() {
        this.configs = {}
        return this
    }

    withResponseType(type) {
        if (type === 'blob') {
            this.configs.xhrFields = {
                responseType: 'blob'
            };
        } else {
            this.configs.dataType = type;
        }
        return this
    }

    withButtonLoading(button) {
        this.button = button;
        return this;
    }

    get(url, params = {}) {
        return this.request(url, 'GET', params)
    }

    post(url, data = {}) {
        return this.request(url, 'POST', data)
    }

    request(url, method, data) {
        const options = {
            url: url,
            type: method,
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            ...this.configs,
        }

        if (this.button && typeof FilamentMedia !== 'undefined') {
            const button = this.button;
            const originalBeforeSend = options.beforeSend;
            const originalComplete = options.complete;

            options.beforeSend = (xhr, settings) => {
                FilamentMedia.showButtonLoading(button);
                if (originalBeforeSend) {
                    originalBeforeSend(xhr, settings);
                }
            };

            options.complete = (xhr, status) => {
                FilamentMedia.hideButtonLoading(button);
                if (originalComplete) {
                    originalComplete(xhr, status);
                }
            };
        }

        return new Promise((resolve, reject) => {
            $.ajax(options)
                .done((data, textStatus, jqXHR) => {
                    const responseHeaders = {};
                    const headers = jqXHR.getAllResponseHeaders().trim().split(/[]+/);
                    headers.forEach((line) => {
                        const parts = line.split(': ');
                        const header = parts.shift();
                        const value = parts.join(': ');
                        if (header) {
                            responseHeaders[header.toLowerCase()] = value;
                        }
                    });

                    resolve({
                        data: data,
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        headers: responseHeaders,
                        config: options,
                        request: jqXHR
                    });
                })
                .fail((jqXHR, textStatus, errorThrown) => {
                    reject({
                        response: {
                            data: jqXHR.responseJSON || jqXHR.responseText,
                            status: jqXHR.status,
                            statusText: jqXHR.statusText,
                            headers: jqXHR.getAllResponseHeaders(),
                        },
                        message: errorThrown
                    });
                });
        });
    }
}

export const $httpClient = new HttpClient()
