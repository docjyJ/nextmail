export type OCSResponse<T> = {
    ocs: {
        data: T;
        meta: {
            status: string;
            statuscode: number;
            message?: string;
            totalitems?: string;
            itemsperpage?: string;
        };
    };
};

export type ServerConfig = {
    id: number;
    endpoint: string;
    username: string;
    password: string;
};

export type ServerStatus = {
    type: 'success' | 'warning' | 'error';
    text: string;
};
