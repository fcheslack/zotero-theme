

jQuery(document).ready(function($) {
	// const form = document.getElementById('Form_Comment') ?? document.querySelector('');
	const textbox = document.querySelector('form#Form_Comment textarea[name="Body"]') ?? document.querySelector('div#DiscussionForm textarea#Form_Body');

	const config = window.VanillaZotero.config;
	const readCookie = window.VanillaZotero.Util.readCookie;
	const getUserID =  window.VanillaZotero.Util.getUserID;

	const sessionCookie = readCookie(config.zoteroSessionCookieName);

	const uploadFile = async (file) => {
		console.log('uploadFile');
		let formData = new FormData()

		formData.append('forum_image', file);

		let headers = {
			Authorization: sessionCookie,
		};

		console.log(`fetching ${config.uploadUrl}`);
		return fetch(config.uploadUrl, {
			credentials: 'include',
			method: 'POST',
			headers: headers,
			// mode: "no-cors"
			body: formData
		}).then((resp) => {
			//get json result from response
			if(resp.ok) {
				return resp.json();
			} else {
				throw resp.json();
			}
		}).then((data) => {
			//success, get url from response
			console.log(data);
			if (!data.url) {
				throw new Error('no url result from image upload');
			}
			ForumImages.uploadedUrls.push(data.url);
			// addImageUrlToForm(data.url);
			addImageLinkToPost(data.url);
		}).catch((err) => {
			// Error. Inform the user
			console.error(err);
			
		});
	};

	const deleteUploadedFile = async (url) => {
		console.log('deleteUploadedFile');

		const filePath = url.substring(url.indexOf('images/forums/u'));
		const queryString = encodeURIComponent(filePath);
		const requestUrl = `${config.uploadUrl}?forum_image=${queryString}`;

		let headers = {
			Authorization: sessionCookie,
		};

		console.log(`fetching ${requestUrl}`);
		return fetch(requestUrl, {
			credentials: 'include',
			method: 'DELETE',
			headers: headers,
			// mode: "no-cors"
		}).then((resp) => {
			//get json result from response
			if(resp.ok) {
				return resp.json();
			} else {
				throw resp.json();
			}
		}).then((data) => {
			removeImageLinkFromPost(url);
			//remove from list of uploaded urls and refresh previews to remove
			const index = ForumImages.uploadedUrls.indexOf(url);
			console.log(ForumImages.uploadedUrls);
			ForumImages.uploadedUrls.splice(index, 1);
			console.log(ForumImages.uploadedUrls);
		}).catch((err) => {
			// Error. Inform the user
			console.error(err);
		});
	};

	//just add url and let forum auto-link
	const addImageLinkToPost = (url) => {
		// const linkText = `<a href="${url}">Saved Image</a>`;
		// textbox.value = textbox.value + `\n\n${linkText}\n`;
		textbox.value = textbox.value + `\n\n${url}\n`;
		textbox.focus();
		textbox.selectionEnd = textbox.value.length;
	};

	//remove forum image links from comment textarea
	const removeImageLinkFromPost = (url) => {
		textbox.value = textbox.value.replaceAll(url, '');
		textbox.focus();
		textbox.selectionEnd = textbox.value.length;
	};

	//detect urls matching uploaded forum images in the comment textarea
	const detectImageUrls = () => {
		console.log('detectImageUrls');
		const forumImageRegex = /http[\S]*\/images\/forums\/u[0-9]+\/[a-zA-Z0-9]{20}(\.jpg|\.png|\.gif)?/g;
		const matches = textbox.value.match(forumImageRegex);
		if (matches == null) {
			return;
		}

		const loggedInUserID = getUserID();
		if(!loggedInUserID) {
			console.log('no logged in user');
			return;
		}

		//if matches are for current user, add them to uploaded URLs array to allow deletion
		matches.forEach((url) => {
			let userIDMatches = url.match(/forums\/u([0-9]+)\//);
			let userID = userIDMatches[1];
			if (userID == loggedInUserID) {
				console.log('adding url to ForumImages.uploadedUrls');
				ForumImages.uploadedUrls.push(url);
			}
		});
		
	};		
	
	class ForumImages extends HTMLElement {
		static files = [];
		static uploadMessages = [];
		static uploadedUrls = [];
		static shadow;
	
		constructor() {
			super();
			const template = document.getElementById('forum-images-template');
			const templateContent = template.content;
			
			console.log('setting this.shadow in ForumImages constructor');
			const shadow = this.attachShadow({mode: 'open'});
			ForumImages.shadow = shadow;
			shadow.appendChild(templateContent.cloneNode(true));

			const dropZoneEl = shadow.getElementById('image-drop-zone');

			const handleFiles = (selectedFiles) => {
				[...selectedFiles].forEach((file, i) => {
					let fileSizeKB = file.size / 1024;
					let fileSizeMB = fileSizeKB / 1024;
					console.log(file);
					console.log(`… file[${i}].name = ${file.name}, ${fileSizeKB}KB (${fileSizeMB}MB)`);
					const extensionRegex = /(\.png|\.gif|\.jpg|\.jpeg)$/i;
					if (!extensionRegex.test(file.name)) {
						alert(`Sorry, that file is not allowed. Images must be .png, .gif, .jpg, or .jpeg`);
						return;
					}
					if (fileSizeMB > config.fileSizeLimitMB) {
						alert(`File too large. Must be under ${config.fileSizeLimitMB} MB.`);
						return;
					}
					ForumImages.files.push(file);
					uploadFile(file).then(() => {
						ForumImages.previewUploadedUrls();
						//save draft with image link
						let draftButton = document.querySelector('a.Button.DraftButton');
						if (!draftButton) {
							draftButton = document.querySelector('input.Button.DraftButton');
						}
						draftButton.click();
					});
				});
			};
		
			const imageUploadDropHandler = (ev) => {
				console.log("File(s) dropped");
				
				// Prevent default behavior (Prevent file from being opened)
				ev.stopPropagation();
				ev.preventDefault();
				
				dropZoneEl.classList.remove('highlight');
		
				const files = ev.dataTransfer.files;
				handleFiles(files);
			};
			
			const dragOverHandler = (ev) => {
				console.log("File(s) in drop zone");
				// Prevent default behavior (Prevent file from being opened)
				ev.stopPropagation();
				ev.preventDefault();
		
				dropZoneEl.classList.add('highlight');
			};
			const dragEnterHandler = dragOverHandler;
		
			const dragleaveHandler = (evt) => {
				dropZoneEl.classList.remove('highlight');
		
			};
		
			//show drag/drop target area when "add image" button is clicked
			shadow.querySelector('#add-image-button').addEventListener('click', (evt) => {
				dropZoneEl.classList.remove('hidden');
			});
			
			dropZoneEl.addEventListener('drop', imageUploadDropHandler);
			dropZoneEl.addEventListener('dragover', dragOverHandler);
			dropZoneEl.addEventListener('dragenter', dragEnterHandler);
			dropZoneEl.addEventListener('dragleave', dragleaveHandler);

			shadow.getElementById('file-picker-button').addEventListener('click', (evt) => {
				evt.preventDefault();
				evt.stopPropagation();
				shadow.getElementById('fileElem').showPicker();
			});

			shadow.getElementById('fileElem').addEventListener('change', function(evt) {
				handleFiles(this.files);
			});

		}

		static previewUploadedUrls() {
			// console.log('previewUploadedUrls');
			const shadow = this.shadow;
			const uploadedUrlsPreviewEl = shadow.getElementById('image-upload-previewUrls');

			uploadedUrlsPreviewEl.innerHTML = '';
			let h = '';
			this.uploadedUrls.forEach((url) => {
				let previewLink = `<preview-link href="${url}"></preview-link>`;
				h += previewLink;
				// let previewLink = document.createElement('preview-link');
				// console.log(`setting href for preview to ${url}`);
				// previewLink.setAttribute('href', url);
				// previewLink.previewUploadedUrls = this.previewUploadedUrls;
				// uploadedUrlsPreviewEl.appendChild(previewLink);
			});
			uploadedUrlsPreviewEl.innerHTML = h;
		}
	}

	window.ForumImages = ForumImages;

	class PreviewLink extends HTMLElement {
		constructor() {
			super();
			let template = document.getElementById('url-preview-template');
			let templateContent = template.content;
	
			const shadow = this.attachShadow({mode: 'open'});
			shadow.appendChild(templateContent.cloneNode(true));
	
			shadow.querySelector('img').setAttribute('src', this.getAttribute('href'));
			shadow.querySelector('a').setAttribute('href', this.getAttribute('href'));
			
			shadow.querySelector('a.toggle-preview').addEventListener('click', (evt) => {
				evt.stopPropagation();
				evt.preventDefault();
				if (this.getAttribute('show') == null) {
					console.log('toggle-preview clicked, show attribute is null');
					this.setAttribute('show', "true");
					shadow.querySelector('img').classList.remove('hidden');
					shadow.querySelector('a.toggle-preview').innerText = 'Hide';
				} else {
					console.log('toggle-preview clicked, show attribute is not null');
					this.removeAttribute('show');
					shadow.querySelector('img').classList.add('hidden');
					shadow.querySelector('a.toggle-preview').innerText = 'Show';
				}
			});
	
			shadow.querySelector('a.delete-image').addEventListener('click', (evt) => {
				evt.stopPropagation();
				evt.preventDefault();
				
				const url = this.getAttribute('href');
				deleteUploadedFile(url).then(() => {
					// document.removeChild(this);
					ForumImages.previewUploadedUrls();
				});
			});

	
			/*
			const link = document.createElement('a');
			link.setAttribute('href', this.getAttribute('href'));
			link.setAttribute('target', '_blank');
			link.innerText = "Uploaded Image";
	
			const img = document.createElement('img');
			img.setAttribute('src', this.getAttribute('href'));
	
			shadow.appendChild(link);
			shadow.appendChild(img);
			*/
		}

		// previewUploadedUrls = ForumImages.previewUploadedUrls;
	}
	
	customElements.define('preview-link', PreviewLink);
	customElements.define('forum-images', ForumImages);


	//create forum-image element in container
	const forumImages = document.createElement('forum-images');
	document.querySelector('#forum-images-container').appendChild(forumImages);

	detectImageUrls();
	if (ForumImages.uploadedUrls.length) {
		console.log('previewing uploaded Urls');
		ForumImages.previewUploadedUrls();
	}
	console.log(ForumImages.uploadedUrls);

	// $('#image-drop-zone').on('drop', dropHandler);
	// $('#image-drop-zone').on('dragOver', dragOverHandler);	

	  
});