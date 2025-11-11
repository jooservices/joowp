import axios, { AxiosInstance } from 'axios';

declare global {
    interface Window {
        axios: AxiosInstance;
    }
}

const httpClient = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
});

window.axios = httpClient;

export { httpClient };

