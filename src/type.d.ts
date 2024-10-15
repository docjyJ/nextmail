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

export type MailServerForm = {
	id: string;
	endpoint: string;
	username: string;
	password: string;
};

export type MailServer = {
	id: string;
	endpoint: string;
	username: string;
	health: 'bad_network' | 'bad_server' | 'invalid' | 'success' | 'unauthorized';
};

export type MailUser = {
	id: string;
	name: string;
	email: string | null;
	admin: boolean;
	quota: number | null;
};

export type UserResponse = {
	users: {
		id: string;
		displayname: string;
		email: string | null;
	}[];
};
