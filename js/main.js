const requestURLGlobal = 'backend.php';

const mainContainer = document.querySelector('.main-container');
const btnLogout  = document.querySelector('.btn-logout');
const btnExport  = document.querySelector('.btn-export');
const devicesAllTable = document.querySelector('.devices-all-table');
const devicesAllBody = document.querySelector('.devices-all-body');
const inputDeviceName = document.querySelector('.input-device-name');
const inputDevicePlatform = document.querySelector('.input-device-platform');
const inputDeviceService = document.querySelector('.input-device-service');
const inputDeviceOwner = document.querySelector('.input-device-owner');
const inputDeviceContact_info = document.querySelector('.input-device-contact_info');
const inputDeviceManager = document.querySelector('.input-device-manager');
const inputDevicecomments = document.querySelector('.input-device-comments');
const triangle = document.querySelector('.triangle');
const btnApplySettings = document.querySelector('.btn-apply-settings');
const btnAddDevice = document.querySelector('.btn-add-device');
const btnClearSettings = document.querySelector('.btn-clear-settings');
const btnSignIn = document.querySelector('.btn-signIn');
const inputDeviceSettings = document.querySelectorAll('.input-device');

const btnDialogModal = document.getElementById('btnDialogModal');
const titleDialogModal = document.getElementById('titleDialogModal');
const questionDialogModal = document.getElementById('questionDialogModal');
const signInUsername = document.getElementById('signIn_username');
const signInPassword = document.getElementById('signIn_password');

let dataTableObj;

const objCallbackFunctions = {
	'doGetDevicesAll': function (parameter) { 
		doGetDevicesAll(parameter);
	},
	'doApplyDeviceSettings': function (parameter) { 
		doApplyDeviceSettings(parameter);
	},
	'doAddDevice': function (parameter) { 
		doAddDevice(parameter);
	},
	'doDeleteDevice': function (parameter) { 
		doDeleteDevice(parameter);
	},
	'doSignIn': function (parameter) {
		doSignIn(parameter);
	},
};

const backendDispatch = function (request) {
	if (request.status == 200) {
		ans_str = JSON.parse(request.response);
		if (window.ans_str.success && ans_str.success.code == 1) {
			objCallbackFunctions[ans_str.success.call_name](ans_str.success.answer);
		}
		else
		{
			objCallbackFunctions['errorCallBack'](ans_str.error);
		}
	} 
	else if(request.status == 401)
	{
		showSignIn();
	}
	else {
		console.log(request);
		return false;
	}
};

const fillDevices = function(){
	const request = new XMLHttpRequest();
	const data = new FormData();
	data.append('call', 'doGetDevicesAll');
	request.open("POST", requestURLGlobal, true);
	request.send(data);
	request.onload = function () {
		backendDispatch(request);
	}	
};

const doGetDevicesAll = function(json_answer){
	if(!!dataTableObj)
	{
		dataTableObj.clear().destroy();
	}
	devicesAllBody.textContent = '';
	json_answer.forEach(function ({
		id, name, platform, service, owner, contact_info, manager, comments
	}) 
	{
		let deviceDataID = `device-data-${id}`;
		devicesAllBody.insertAdjacentHTML('beforeend', `
			<tr id="${deviceDataID}">
				<td>
					<span>${name}</span>
				</td>
				<td>
					<span>${platform}</span>
				</td>
				<td>
					<span>${service}</span>
				</td>
				<td>
					<span>${owner}</span>
				</td>
				<td>
					<span>${contact_info}</span>
				</td>
				<td>
					<span>${manager}</span>
				</td>
				<td>
					<span class="comment-text crop-height">${comments}</span>
				</td>
				<td>
					<div class="action-buttons d-block d-xl-flex">
						<button class="btn btn-primary btn-sm action-button" data-device_id="${id}" data-param="modify"
						 data-name="${name}"
						 data-platform="${platform}"
						 data-service="${service}"
						 data-owner="${owner}"
						 data-contact_info="${contact_info}"
						 data-comments="${comments}"
						 data-manager="${manager}"
						 data-row-id="${deviceDataID}">Modify</button>
						<button class="btn btn-danger btn-sm action-button" data-name="${name}" data-device_id="${id}" data-param="delete">Delete</button>
					</div>
				</td>
			</tr>
		`);
	});
	
	dataTableObj = $('#devices-all-table').DataTable( {
	"columnDefs": [
		{ "orderable": false, "targets": 7 },
		{ "width": "15%", "targets": [1, 2, 3] },
	],
	"autoWidth": false,
	"paging": false,
	});
	mainContainer.classList.remove('d-none');
};
const doApplyDeviceSettings = function({id, name, platform, service, owner, contact_info, manager, comments}){
	fillDevices();
	clearDeviceSettings();
}

const doAddDevice = function({id, name, platform, service, owner, contact_info, manager, comments})
{
	let deviceDataID = `device-data-${id}`;

	const newTR = document.createElement('tr');
	newTR.id = deviceDataID;
	newTR.insertAdjacentHTML('beforeend', `
		<td>
			<span>${name}</span>
		</td>
		<td>
			<span>${platform}</span>
		</td>
		<td>
			<span>${service}</span>
		</td>
		<td>
			<span>${owner}</span>
		</td>
		<td>
			<span>${contact_info}</span>
		</td>
		<td>
			<span>${manager}</span>
		</td>
		<td>
			<span class="comment-text crop-height">${comments}</span>
		</td>
		<td>
			<div class="action-buttons d-block d-xl-flex">
				<button class="btn btn-primary btn-sm action-button" data-device_id="${id}" data-param="modify"
				data-name="${name}"
				data-platform="${platform}"
				data-service="${service}"
				data-owner="${owner}"
				data-contact_info="${contact_info}"
				data-comments="${comments}"
				data-manager="${manager}"
				data-row-id="${deviceDataID}">Modify</button>
				<button class="btn btn-danger btn-sm action-button" data-name="${name}" data-device_id="${id}" data-param="delete">Delete</button>
			</div>
		</td>
	`);
	dataTableObj.row.add(newTR).draw(false);
	clearDeviceSettings();
};

const doDeleteDevice = function ({id}){
	fillDevices();
	$('#dialogModal').modal('hide');
};

const doSignIn = function (parameters){
	document.location.href='./index.html';
}


const deviceAction = function(event){
	const target = event.target;
	const actionButton = target.closest('.action-button');
	if (!!actionButton)
	{
		const action = actionButton.dataset['param'];
		switch (action) {
			case 'modify':
				modifyDeviceSettings(actionButton.dataset);
				break;
			case 'delete':
				reqDeleteDevice(actionButton.dataset);
				break;
		}
	}
};

const modifyDeviceSettings = function(deviceParams){
	triangle.classList.remove('triangle-down');
	triangle.classList.add('triangle-up');
	inputDeviceName.value = deviceParams['name'];
	inputDevicePlatform.value = deviceParams['platform'];
	inputDeviceService.value = deviceParams['service'];
	inputDeviceOwner.value = deviceParams['owner'];
	inputDeviceContact_info.value = deviceParams['contact_info'];
	inputDeviceManager.value = deviceParams['manager'];
	inputDevicecomments.innerText = deviceParams['comments'];
	btnApplySettings.dataset['device_id'] = deviceParams['device_id'];
	$('.collapse').collapse('show');
};

const signIn = function(){
	const username = signInUsername.value.trim().toLowerCase();
	const password = signInPassword.value.trim();
	if (username == '' || password == '')
	{
		return false;
	}
	const request = new XMLHttpRequest();
	const data = new FormData();
	data.append('call', 'doSignIn');
	data.append('username', username);
	data.append('password', password);
	request.open("POST", requestURLGlobal, true);
	request.send(data);
	request.onload = function () {
		backendDispatch(request);
	}	
};

const logOut = function(){
	if (!mainContainer.classList.contains('d-none')){
		mainContainer.classList.add('d-none');
	}
	const request = new XMLHttpRequest();
	const data = new FormData();
	data.append('call', 'doLogOut');
	request.open("POST", requestURLGlobal, true);
	request.send(data);
	request.onload = function () {
		backendDispatch(request);
	}
}
const dataExport = function() {
	// const request = new XMLHttpRequest();
	// const data = new FormData();
	// data.append('call', 'doDataExport');
	// request.open("POST", 'export.php', true);
	// request.send(data);
	// request.onload = function () {
	// 	// backendDispatch(request);
	// }
	document.location.href='./backend.php?action=doDataExport';
}
const triangleToggle = (event) =>{
	const target = event.target;
	if (target.classList.contains('triangle-down'))
	{
		triangle.classList.remove('triangle-down');
		triangle.classList.add('triangle-up');
		$('.collapse').collapse('show');
	}
	else{
		triangle.classList.add('triangle-down');
		triangle.classList.remove('triangle-up');
		$('.collapse').collapse('hide');
	}
};

const clearDeviceSettings = () =>{
	inputDeviceSettings.forEach(function (inputField){
		inputField.value = '';
		inputField.innerText = '';
	});
};

const applyDeviceSettings = (event) =>{
	const deviceID = event.target.dataset['device_id'];
	const request = new XMLHttpRequest();
	const data = new FormData();
	data.append('call', 'doApplyDeviceSettings');
	data.append('id', deviceID);
	data.append('name', inputDeviceName.value);
	data.append('platform', inputDevicePlatform.value);
	data.append('service', inputDeviceService.value);
	data.append('owner', inputDeviceOwner.value);
	data.append('contact_info', inputDeviceContact_info.value);
	data.append('manager', inputDeviceManager.value);
	data.append('comments', inputDevicecomments.innerText);
	request.open("POST", requestURLGlobal, true);
	request.send(data);
	request.onload = function () {
		backendDispatch(request);
	}
};

const addDevice = (event) => {
	const request = new XMLHttpRequest();
	const data = new FormData();
	data.append('call', 'doAddDevice');
	data.append('name', inputDeviceName.value);
	data.append('platform', inputDevicePlatform.value);
	data.append('service', inputDeviceService.value);
	data.append('owner', inputDeviceOwner.value);
	data.append('contact_info', inputDeviceContact_info.value);
	data.append('manager', inputDeviceManager.value);
	data.append('comments', inputDevicecomments.innerText);
	request.open("POST", requestURLGlobal, true);
	request.send(data);
	request.onload = function () {
		backendDispatch(request);
	}
};
const reqDeleteDevice = (deviceParams) =>{
	titleDialogModal.innerText = 'Delete node';
	questionDialogModal.innerText = `Do you really want to delete the node: ${deviceParams['name']}?`;
	btnDialogModal.setAttribute('modal-command', 'deleteDevice');
	btnDialogModal.dataset['id'] = deviceParams['device_id'];
	$('#dialogModal').modal({
		keyboard: true
	});
};

const deleteDevice = (deviceID) => {
	const request = new XMLHttpRequest();
	const data = new FormData();
	data.append('call', 'doDeleteDevice');
	data.append('id', deviceID);
	request.open("POST", requestURLGlobal, true);
	request.send(data);
	request.onload = function () {
		backendDispatch(request);
	}
};
const removeElement = (id) => {
    const elem = document.getElementById(id);
    if (elem)	return elem.parentNode.removeChild(elem);
    return false;
};

const confirmDialog = (event) =>
{
	const target = event.target;
	const modalCommand = target.getAttribute('modal-command');
	switch(modalCommand)
	{
		case 'deleteDevice':
			deleteDevice(target.dataset['id']);
			break;
	}
};

const showSignIn = () =>
{
	$('#modal_signIn').modal('show');
};

devicesAllBody.addEventListener('click', deviceAction);
triangle.addEventListener('click', triangleToggle);
btnClearSettings.addEventListener('click', clearDeviceSettings);
btnApplySettings.addEventListener('click', applyDeviceSettings);
btnAddDevice.addEventListener('click', addDevice);
btnDialogModal.addEventListener('click', confirmDialog);
btnSignIn.addEventListener('click', signIn);
btnLogout.addEventListener('click', logOut);
btnExport.addEventListener('click', dataExport);


fillDevices();
clearDeviceSettings();