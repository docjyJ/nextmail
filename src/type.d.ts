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
	name: string;
	endpoint: string;
	username: string;
	health: 'bad_network' | 'bad_server' | 'invalid' | 'success' | 'unauthorized';
};

export type MailUserForm = {
	id: string;
	server_id: string | null,
	admin: boolean;
	quota: number | null;
};

export type MailUser = {
	id: string;
	server_id: string | null,
	name: string;
	email: string | null;
	admin: boolean;
	quota: number | null;
};

export type MailGroupForm = {
	id: string;
	server_id: string | null,
	email: string;
};

export type MailGroup = {
	id: string;
	server_id: string | null,
	name: string;
	email: string | null;
};
