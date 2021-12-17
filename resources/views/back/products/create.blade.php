@extends('back.master')

@section('title', __('Create product'))

@section('additional_head_tags')

<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>
<script src="{{ asset_('assets/wavesurfer.min.js') }}"></script>

@endsection


@section('content')
<div id="product" vhidden>
	<form class="ui large form main" method="post" enctype="multipart/form-data" action="{{ route('products.store') }}">
		<div class="field">
			<input type="submit" id="submit" class="d-none">
			<button class="ui icon labeled large pink circular button" :class="{disabled: anyInputOff()}" type="button" id="save">
			  <i class="save outline icon"></i>
			  {{ __('Create') }}
			</button>
			<a class="ui icon labeled large yellow circular button" :class="{disabled: anyInputOff()}" href="{{ route('products') }}">
				<i class="times icon"></i>
				{{ __('Cancel') }}
			</a>
		</div>
		
		@if($errors->any())
      @foreach ($errors->all() as $error)
			<div class="ui negative fluid small message">
				<i class="times icon close"></i>
				{{ $error }}
			</div>
      @endforeach
		@endif

		<div class="ui fluid divider"></div>

		<div class="ui one column grid">
			<div class="column tabs">
				<div class="ui top attached tabular menu">
			    <a class="active item" data-tab="overview">{{ __('Overview') }}</a>
			  	<a class="item" data-tab="description">{{ __('Description') }}</a>
				  <a class="item" data-tab="hidden-content">{{ __('Hidden content') }}</a>
				  <a class="item" data-tab="pricing">{{ __('Pricing') }}</a>
				  <a class="item" :class="{'d-none': itemType !== 'ebook'}" data-tab="table-of-contents">{{ __('Table of contents') }}</a>
				  <a class="item" data-tab="faq">{{ __('FAQ') }}</a>
				  <a class="item" data-tab="additional-fields">{{ __('Additional fields') }}</a>
			  </div>

			  <div class="ui tab segment active" data-tab="overview">
			  	<input type="hidden" name="is_dir" value="{{ isFolderProcess() ? '1' : '0' }}">

			  	<div class="field">
			  		<label>{{ __('Type') }}</label>
			  		<div class="ui selection floating search dropdown">
						  <input type="hidden" name="type" @change="setItemType($event)" v-model="itemType">
						  <div class="default text">-</div>
						  <div class="menu">
						  	@foreach(config('app.item_types') ?? [] as $k => $v)
								<div class="item" data-value="{{ $k }}">{{ __($v) }}</div>
								@endforeach
						  </div>
						</div>
			  	</div>

			  	<div class="field">
						<label>{{ __('Name') }}</label>
						<input type="text" name="name" placeholder="..." value="{{ old('name') }}" autofocus required>
					</div>

					<div class="field">
						<label>{{ __('Short description') }}</label>
						<textarea name="short_description" cols="30" rows="3">{{ old('short_description') }}</textarea>
					</div>

					<div class="field">
						<label>{{ __('Category') }} <sup>({{ __('Required') }})</sup></label>
						<div class="ui selection floating search dropdown">
						  <input type="hidden" name="category" value="{{ old('category') }}">
						  <div class="default text">-</div>
						  <div class="menu">
						  	@foreach($category_parents as $category_parent)
								<div class="item" data-value="{{ $category_parent->id }}">
									{{ ucfirst($category_parent->name) }}
								</div>
						  	@endforeach
						  </div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Subategories') }} <sup>({{ __('Optional') }})</sup></label>
						<div class="ui multiple selection floating search dropdown" id="subcategories">
							<input type="hidden" name="subcategories" value="{{ old('subcategories') }}">
							<div class="default text">{{ __('Select subcategory') }}</div>
							<div class="menu"></div>
						</div>
					</div>

					<div class="ui hidden divider"></div>

					<div class="mb-1" v-if="itemType === 'audio'">
						<div class="field">
							<label>{{ __('Label') }}</label>
							<input type="text" name="label" value="{{ old('label') }}">
						</div>

						<div class="field">
							<label>{{ __('BPM') }}</label>
							<input type="text" value="{{ old('bpm') }}">
						</div>

						<div class="field">
							<label>{{ __('Bit Rate') }}</label>
							<input type="number" value="{{ old('bit_rate') }}">
						</div>
					</div>

					<div class="mb-1" v-if="itemType === 'ebook'">
						<div class="field">
							<label>{{ __('Pages') }}</label>
							<input type="number" name="pages" value="{{ old('pages') }}">
						</div>

						<div class="field">
							<label>{{ __('Language') }}</label>
							<input type="text" name="language" value="{{ old('language') }}">
						</div>

						<div class="field">
							<label>{{ __('Formats') }}</label>
							<input type="text" name="formats" value="{{ old('formats') }}">
						</div>

						<div class="field">
							<label>{{ __('Words') }}</label>
							<input type="text" name="words" value="{{ old('words') }}">
						</div>
					</div>

					<div class="field">
						<label>{{ __('Tools used') }} <i class="exclamation circle icon" title="languages, libraries, frameworks..."></i></label>
						<input type="text" name="software" value="{{ old('software') }}" placeholder="...">
					</div>

					<div class="mb-1" v-if="itemType === '-'">
						<div class="field">
							<label>{{ __('Database used') }} <i class="exclamation circle icon" title="MongoDB, MySQL, SQLite..."></i></label>
							<input type="text" name="database" value="{{ old('database') }}" placeholder="...">
						</div>

						<div class="ui hidden divider"></div>

						<div class="field">
							<label>{{ __('Compatible browsers') }}</label>
							<input type="text" name="compatible_browsers" value="{{ old('compatible_browsers') }}" placeholder="...">
						</div>

						<div class="ui hidden divider"></div>
						
						<div class="field">
							<label>{{ __('Compatible OS') }}</label>
							<input type="text" name="compatible_os" value="{{ old('compatible_os') }}" placeholder="...">
						</div>
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Included files') }}</label>
						<input type="text" name="included_files" value="{{ old('included_files') }}" placeholder="...">
					</div>

					<div class="field">
						<label>{{ __('Version') }}</label>
						<input type="text" name="version" value="{{ old('version') }}" placeholder="...">
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Release date') }}</label>
						<input type="date" name="release_date" value="{{ old('release_date') }}" placeholder="...">
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Latest update') }}</label>
						<input type="date" name="last_update" value="{{ old('last_update') }}" placeholder="...">
					</div>

					<div class="field">
						<label>{{ __('High resolution') }}</label>
						<div class="ui selection floating dropdown">
							<input type="hidden" name="high_resolution" value="{{ old('high_resolution') }}">
							<div class="text">...</div>
							<div class="menu">
								<a class="item" data-value="">-</a>
								<a class="item" data-value="1">{{ __('Yes') }}</a>
								<a class="item" data-value="0">{{ __('No') }}</a>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Authors') }}</label>
						<input type="text" name="authors" value="{{ old('authors') }}">
					</div>

					<div class="field">
						<label>{{ __('Tags') }} <sup>({{ __('Optional') }})</sup></label>
						<input type="text" name="tags" placeholder="{{ __('Comma-separated values') }}" value="{{ old('tags') }}">
					</div>

					<div class="field">
						<label>{{ __('Preview link') }} <sup>({{ __('Optional') }})</sup></label>
						<input type="text" name="preview_url" class="d-block" placeholder="https://..." value="{{ old('preview_url') }}">
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Quantity in stock') }}</label>
						<input type="number" name="stock" value="{{ old('stock') }}">
						<small><i class="circular exclamation small red icon"></i>{{ __('Leave empty if not applicable.') }}</small>
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Enable license') }}</label>
						<div class="ui floating selection dropdown">
							<input type="hidden" name="enable_license" value="{{ old('enable_license') }}">
							<div class="text">...</div>
							<div class="menu">
								<a class="item" data-value="1">{{ __('Yes') }}</a>
								<a class="item" data-value="0">{{ __('No') }}</a>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Available via subscription only') }}</label>
						<div class="ui floating selection dropdown">
							<input type="hidden" name="for_subscriptions" value="{{ old('for_subscriptions', '0') }}">
							<div class="text">...</div>
							<div class="menu">
								<a class="item" data-value="1">{{ __('Yes') }}</a>
								<a class="item" data-value="0">{{ __('No') }}</a>
							</div>
						</div>
					</div>

					@if(config('app.products_by_country_city'))
					<div class="field">
						<label>{{ __('Country') }}</label>
						<div class="ui floating search selection dropdown countries">
							<input type="hidden" name="country_city[country]" value="{{ old('country_city.country') }}">
							<div class="text">...</div>
							<div class="menu">
								<a class="item" data-value=""></a>
								@foreach(config('app.countries_cities') as $country => $cities)
								<a class="item" data-value="{{ $country }}">{{ $country }}</a>
								@endforeach
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('City') }}</label>
						<div class="ui floating search selection dropdown cities">
							<input type="hidden" name="country_city[city]" value="{{ old('country_city.city') }}">
							<div class="text">...</div>
							<div class="menu"></div>
						</div>
					</div>
					@endif

					<div class="files">
						<p class="m-0 bold">{{ __('Files') }}</p>

						<div class="ui four stackable doubling cards">
							<div class="fluid card">
								<div class="content">
									<div class="header">{{ __('Main file') }} <sup>({{ __('Optional') }})</sup></div>
								</div>
								<div class="content">
									<input type="hidden" name="file_host" :value="selectedDrive" class="d-none">
									<input type="hidden" name="file_name" :value="fileId" class="d-none">

									<div v-if="oldUploads.download.length" class="ui fluid large pink label">
										{{ $download }}
										<i class="close icon ml-auto mr-0" @click="deleteExistingFile('{{ "storage/app/downloads/{$download}" }}', 'download')"></i>
									</div>

									<div v-else-if="fileId">
										<div class="ui red fluid label circular-corner">
											@{{ fileId }}
											<i class="close icon ml-auto mr-0" @click="removeSelectedFile"></i>	
										</div>
									</div>

									<div class="w-100" v-else>
										<div v-if="hasProgress('download')">
											<div v-if="uploadInProgress('download')">
												<progress :value="ajaxRequests.cover.progress" max="100"></progress>
												<a  v-if="!finishedUploading('download')" class="ui mini red button circular mb-1-hf" @click="abortUpload('download')">{{ __('Abort upload') }}</a>
											</div>

											<div class="ui fluid large pink label mb-1" v-else>
												@{{ ajaxRequests.download.file_name }}
												<i class="close icon ml-auto mr-0" @click="removeUploadedFile('download')"></i>
											</div>
										</div>

										<div class="ui floating circular fluid dropdown large blue basic button mx-0 files" :class="{disabled: inputIsOff('download')}">
											<div class="text d-block center aligned">{{ __('Browse') }}</div>
											<div class="menu">
												<a class="item" @click="browserMainFile('local')">{{ __('Default') }}</a>

												@if(config('filehosts.amazon_s3.enabled') && !isFolderProcess())
												<a class="item" @click="browserMainFile('amazon_s3')">{{ __('Amazon S3') }}</a>
												@endif

												{{-- @if(config('filehosts.one_drive.enabled'))
												<a class="item" @click="browserMainFile('one_drive')">{{ __('One Drive') }}</a>
												@endif --}}

												@if(config('filehosts.google_drive.enabled'))
												<a class="item" @click="browserMainFile('google')">{{ __('Google Drive') }}</a>
												@endif

												@if(config('filehosts.wasabi.enabled') && !isFolderProcess())
												<a class="item" @click="browserMainFile('wasabi')">{{ __('Wasabi') }}</a>
												@endif

												@if(config('filehosts.dropbox.enabled'))
												<a class="item" @click="browserMainFile('dropbox')">{{ __('DropBox') }}</a>
												@endif

												@if(config('filehosts.yandex.enabled') && !isFolderProcess())
												<a class="item" @click="browserMainFile('yandex')">{{ __('Yandex') }}</a>
												@endif

												<a class="item" @click="browserMainFile('main_file_upload_link')">{{ __('Upload link') }}</a>
												
												@if(!isFolderProcess())
												<a class="item" @click="browserMainFile('main_file_download_link')">{{ __('Download link') }}</a>
												@endif
											</div>
										</div>

										<input type="url" name="main_file_upload_link" :class="{disabled: inputIsOff('download')}" placeholder="{{ __('Upload link') }}" value="{{ old('main_file_upload_link') }}" @change="setDefaultDrive" value="{{ old('main_file_upload_link') }}" class="mt-1">

										@if(!isFolderProcess())
										<input type="url" name="main_file_download_link" :class="{disabled: inputIsOff('download')}" placeholder="{{ __('Download link') }}" value="{{ old('main_file_download_link') }}" @change="setDefaultDrive" value="{{ old('main_file_download_link') }}" class="mt-1">
										@endif
									</div>
								</div>
							</div>

							<div class="fluid card">
								<div class="content">
									<div class="header">{{ __('Cover') }} <sup>({{ __('Required') }})</sup></div>
								</div>
								<div class="content">
									<div v-if="oldUploads.cover.length">
										<div class="ui fluid large pink label">
											{{ $cover }}
											<i class="close icon ml-auto mr-0" @click="deleteExistingFile('{{ "public/storage/covers/{$cover}" }}', 'cover')"></i>
										</div>	
									</div>
									<div class="w-100" v-else>
										<div v-if="hasProgress('cover')">
											<div v-if="uploadInProgress('cover')">
												<progress :value="ajaxRequests.cover.progress" max="100"></progress>
												<a  v-if="!finishedUploading('cover')" class="ui mini red button circular mb-1-hf" @click="abortUpload('cover')">{{ __('Abort upload') }}</a>
											</div>

											<div class="ui fluid large pink label mb-1" v-else>
												@{{ ajaxRequests.cover.file_name }}
												<i class="close icon ml-auto mr-0" @click="removeUploadedFile('cover')"></i>
											</div>
										</div>
										<button class="ui basic large circular blue fluid button" type="button" :class="{disabled: inputIsOff('cover')}" @click="selectFile('cover')">{{ __('Browse') }}</button>
									</div>
								</div>
							</div>


							<div class="fluid card">
								<div class="content">
									<div class="header">{{ __('Screenshots') }} <sup>({{ __('Optional') }})</sup></div>
								</div>
								<div class="content">
									<div v-if="oldUploads.screenshots.length">
										<div class="ui fluid large pink label">
											{{ $screenshots }}
											<i class="close icon ml-auto mr-0" @click="deleteExistingFile('{{ "public/storage/covers/{$screenshots}" }}', 'screenshots')"></i>
										</div>	
									</div>
									<div class="w-100" v-else>
										<div v-if="hasProgress('screenshots')">
											<div v-if="uploadInProgress('screenshots')">
												<progress :value="ajaxRequests.screenshots.progress" max="100"></progress>
												<a  v-if="!finishedUploading('screenshots')" class="ui mini red button circular mb-1-hf" @click="abortUpload('screenshots')">{{ __('Abort upload') }}</a>
											</div>

											<div class="ui fluid large pink label mb-1" v-else>
												@{{ ajaxRequests.screenshots.file_name }}
												<i class="close icon ml-auto mr-0" @click="removeUploadedFile('screenshots')"></i>
											</div>
										</div>
										<button class="ui basic large circular blue fluid button" type="button" :class="{disabled: inputIsOff('screenshots')}" @click="selectFile('screenshots')">{{ __('Browse') }}</button>
									</div>
								</div>
							</div>


							<div class="fluid card">
								<div class="content">
									<div class="header">{{ __('Preview') }} <sup>({{ __('Optional') }})</sup></div>
								</div>

								<div class="content">
									<div v-if="oldUploads.preview.length">
										<div class="ui fluid large pink label">
											{{ $preview }}
											<i class="close icon ml-auto mr-0" @click="deleteExistingFile('{{ "public/storage/previews/{$preview}" }}', 'preview')"></i>
										</div>	
									</div>
									<div class="w-100" v-else>
										<div v-if="hasProgress('preview')">
											<div v-if="uploadInProgress('preview')">
												<progress :value="ajaxRequests.preview.progress" max="100"></progress>
												<a  v-if="!finishedUploading('preview')" class="ui mini red button circular mb-1-hf" @click="abortUpload('preview')">{{ __('Abort upload') }}</a>
											</div>

											<div class="ui fluid large pink label mb-1" v-else>
												@{{ ajaxRequests.preview.file_name }}
												<i class="close icon ml-auto mr-0" @click="removeUploadedFile('preview')"></i>
											</div>
										</div>

										<div class="ui floating circular fluid dropdown preview large blue basic button mx-0" :class="{disabled: inputIsOff('preview')}">
											<div class="text d-block center aligned">{{ __('Browse') }}</div>
											<div class="menu">
												<a class="item" @click="browsePreviewFile('preview')">{{ __('Default') }}</a>
												<a class="item" @click="browsePreviewFile('preview_upload_link')">{{ __('Upload link') }}</a>
												<a class="item" @click="browsePreviewFile('preview_direct_link')">{{ __('Direct link') }}</a>
											</div>
										</div>

										<input type="url" name="preview_upload_link" :class="{disabled: inputIsOff('preview')}" placeholder="{{ __('Upload link') }}" value="{{ old('preview_upload_link') }}" value="{{ old('preview_upload_link') }}" class="mt-1">

										<input type="url" name="preview_direct_link" :class="{disabled: inputIsOff('preview')}" placeholder="{{ __('Direct link') }}" value="{{ old('preview_direct_link') }}" value="{{ old('preview_direct_link') }}" class="mt-1">

										<div class="ui floating circular fluid dropdown large blue basic button mt-1">
											<input type="hidden" name="preview_type" value="{{ old('preview_type') }}">
											<div class="text">...</div>
											<div class="menu">
												<div class="item" data-value="audio">{{ __('Audio') }}</div>
												<div class="item" data-value="video">{{ __('Video') }}</div>
												<div class="item" data-value="zip">{{ __('Zip') }}</div>
												<div class="item" data-value="pdf">{{ __('PDF') }}</div>
												<div class="item" data-value="-">{{ __('Other') }}</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
			  </div>

			  <div class="ui tab segment" data-tab="description">
					<textarea name="overview" class="summernote">{!! old('overview') !!}</textarea>
			  </div>

			  <div class="ui tab segment" data-tab="hidden-content">
					<textarea name="hidden_content" class="summernote">{!! old('hidden_content') !!}</textarea>
			  </div>

			  <div class="ui tab segment" data-tab="pricing">
			    @foreach(config('licenses') ?? [] as $item_type => $licenses)
			  	<div class="table wrapper licenses {{ $item_type }}" v-if="'{{ $item_type }}' === itemType">
				  	<table class="ui basic table unstackable w-100">
					  	@foreach($licenses as $license)
					  	<tr>
					  		<td class="three columns wide"><strong>{{ __($license->name) }}</strong></td>
					  		<td><input type="number" name="license[price][{{ $license->id }}]" step="0.01" placeholder="{{ __('Default price') }}" {{ old("license.price.{$license->id}") }}></td>
					  		<td><input type="number" name="license[promo_price][{{ $license->id }}]" step="0.01" placeholder="{{ __('Promo price') }}" value="{{ old("license.promo_price[$license->id]") }}"></td>
					  	</tr>
					  	@endforeach
					  </table>
				  </div>
			  	@endforeach

			  	<div class="table wrapper free">
				  	<table class="ui basic table unstackable w-100">
					  	<tr>
					  		<td class="three columns wide"><strong>{{ __('Minimum price') }} <sup>({{ __('Optional') }})</sup></strong></td>
					  		<td><input type="number" step="0.0001" name="minimum_price" value="{{ old('minimum_price') }}"></td>
					  	</tr>
					  </table>
				  </div>

			  	<div class="table wrapper free">
				  	<table class="ui basic table unstackable w-100">
					  	<tr>
					  		<td class="three columns wide"><strong>{{ __('Free') }} <sup>({{ __('Optional') }})</sup></strong></td>
					  		<td><input type="text" name="free[from]" value="{{ old('free.from') }}" placeholder="{{ __('From') }} : YYYY-MM-DD"></td>
					  		<td><input type="text" name="free[to]" value="{{ old('free.to') }}" placeholder="{{ __('To') }} : YYYY-MM-DD"></td>
					  	</tr>
					  </table>
				  </div>

				  <div class="table wrapper promo_price">
				  	<table class="ui basic table unstackable w-100">
					  	<tr>
					  		<td class="three columns wide"><strong>{{ __('Promotional price') }} <sup>({{ __('Optional') }})</sup></strong></td>
					  		<td><input type="text" name="promotional_price_time[from]" value="{{ old('promotional_price_time.from') }}" placeholder="{{ __('From') }} : YYYY-MM-DD"></td>
					  		<td><input type="text" name="promotional_price_time[to]" value="{{ old('promotional_price_time.to') }}" placeholder="{{ __('To') }} : YYYY-MM-DD"></td>
					  	</tr>
					  </table>
				  </div>
			  </div>

			  <div class="ui tab segment" data-tab="table-of-contents">
			    <table class="ui celled unstackable single line table" 
						 		   data-dict='{"Header": "{{ __('Header') }}", "Type": "{{ __('Type') }}", "Subheader": "{{ __('Subheader') }}", "Sub-Subheader": "{{ __('Sub-Subheader') }}", "Add": "{{ __('Add') }}", "Remove": "{{ __('Remove') }}"}'>
						<thead>
							<tr>
								<th class="left aligned">{{ __('Type') }}</th>
								<th class="left aligned">{{ __('Text') }}</th>
								<th class="center aligned">{{ __('Action') }}</th>
							</tr>
						</thead>

						<tbody>
							@if(old('text_type'))
							
							@foreach(old('text_type') as $key => $text_type)
							<tr>
								<td>
									<div class="ui floating circular fluid dropdown large basic button mx-0">
										<input type="hidden" name="text_type[{{ $key }}]" value="{{ $text_type }}" class="toc-type">
										<span class="default text">{{ __('Type') }}</span>
										<i class="dropdown icon"></i>
										<div class="menu">
											<a class="item" data-value="header">{{ __('Header') }}</a>
											<a class="item" data-value="subheader">{{ __('Subheader') }}</a>
											<a class="item" data-value="subsubheader">{{ __('Sub-Subheader') }}</a>
										</div>
									</div>
								</td>
								<td class="ten column wide right aligned">
									<input type="text" name="text[{{ $key }}]" placeholder="..." value="{{ old('text')[$key] ?? '' }}" class="toc-text">
								</td>
								<td class="two column wide center aligned actions">
									<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
									<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
								</td>
							</tr>
							@endforeach

							@else

							<tr>
								<td>
									<div class="ui floating circular fluid dropdown large basic button mx-0">
										<input type="hidden" name="text_type[0]" class="toc-type">
										<span class="default text">{{ __('Type') }}</span>
										<i class="dropdown icon"></i>
										<div class="menu">
											<a class="item" data-value="title">{{ __('Header') }}</a>
											<a class="item" data-value="subtitle">{{ __('Subheader') }}</a>
											<a class="item" data-value="subsubtitle">{{ __('Sub-Subheader') }}</a>
										</div>
									</div>
								</td>
								<td class="ten column wide right aligned">
									<input type="text" name="text[0]" placeholder="..." class="toc-text">
								</td>
								<td class="two column wide center aligned actions">
									<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
									<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
								</td>
							</tr>

							@endif
						</tbody>
					</table>
			  </div>

			  <div class="ui tab segment" data-tab="faq" data-dict='{"Question": "{{ __('Question') }}", "Answer": "{{ __('Answer') }}", "Remove": "{{ __('Remove') }}", "Add": "{{ __('Add') }}"}'>
						@if(old('question') && old('answer'))

							@foreach(old('question') ?? [] as $k => $qa)
								<div class="ui segment">
									<div class="field">
										<label>{{ __('Question') }}</label>
										<input type="text" name="question[{{ $k }}]" class="faq-question" placeholder="..." value="{{ $qa }}">
									</div>
									<div class="field">
										<label>{{ __('Answer') }}</label>
										<textarea name="answer[{{ $k }}]" cols="30" rows="3" class="faq-answer" placeholder="...">{{ old('answer')[$k] ?? '' }}</textarea>
									</div>
									<div class="actions right aligned">
										<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
										<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
									</div>
								</div>
							@endforeach

						@else

						<div class="ui segment">
							<div class="field">
								<label>{{ __('Question') }}</label>
								<input type="text" name="question[0]" class="faq-question" placeholder="...">
							</div>
							<div class="field">
								<label>{{ __('Answer') }}</label>
								<textarea name="answer[0]" cols="30" rows="3" class="faq-answer" placeholder="..."></textarea>
							</div>
							<div class="actions right aligned">
								<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
								<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
							</div>
						</div>

						@endif
				</div>

				<div class="ui tab segment" data-tab="additional-fields" data-dict='{"Name": "{{ __('Name') }}", "Value": "{{ __('Value') }}", "Remove": "{{ __('Remove') }}", "Add": "{{ __('Add') }}"}'>
					@if(old('name') && old('answer'))

							@foreach(old('_name_') ?? [] as $k => $qa)
								<div class="ui segment">
									<div class="two fields">
										<div class="three columns wide field">
											<label>{{ __('Name') }}</label>
											<input type="text" name="_name_[{{ $k }}]" class="addtional-info-name" placeholder="..." value="{{ $qa }}">
										</div>
										<div class="thirteen columns wide field">
											<label>{{ __('Value') }}</label>
											<input type="text" name="_value_[{{ $k }}]" class="addtional-info-value" placeholder="..." value="{{ old('_value_')[$k] ?? '' }}">
										</div>
									</div>
									<div class="actions right aligned">
										<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
										<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
									</div>
								</div>
							@endforeach

						@else

						<div class="ui segment">
							<div class="two fields">
								<div class="three columns wide field">
									<label>{{ __('Name') }}</label>
									<input type="text" name="_name_[0]" class="addtional-info-name" placeholder="...">
								</div>
								<div class="thirteen columns wide field">
									<label>{{ __('Value') }}</label>
									<input type="text" name="_value_[0]" class="addtional-info-value" placeholder="...">
								</div>
							</div>
							<div class="actions right aligned">
								<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
								<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
							</div>
						</div>

						@endif
				</div>
			</div>

			<div class="ui modal" id="files-list">
				<div class="content head p-1">
					<h3>@{{ drivesTitles[selectedDrive] }}</h3>
					
					<div class="ui icon input" v-if="!/yandex|amazon_s3|wasabi/.test(selectedDrive)">
					  <input type="text" placeholder="{{ __('Folder') }}..." v-model="parentFolder" spellcheck="false">
					  <i class="paper plane outline link icon" @click="setFolder"></i>
					</div>
				</div>

				<div class="content body" v-if="selectedDrive">
					<div class="ui six cards">

						<a href="javascript:void(0)" 
							 class="ui card" 
							 v-for="item in mainFilesList[selectedDrive]" 
							 :title="item.name"
							 @click="setSelectedFile(item.id)">
							<div class="image">
						    <img :src="getFileExtension(item)">
						  </div>
						  <div class="content p-0">
						  	<h4 class="header">
						  		@{{ item.name }}
						  	</h4>
						  </div>
						</a>

					</div>
				</div>

				<div class="actions">
					<div class="ui icon input large">
					  <input type="text" placeholder="{{ __('Search') }}..." v-model="searchFile" spellcheck="false">
					  <i class="search link icon" @click="searchFiles"></i>
					</div>

					<button v-if="googleDriveNextPageToken && selectedDrive === 'google'" 
									class="ui blue large circular button" 
									type="button"
									@click="googleDriveLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="amazonS3Marker && selectedDrive === 'amazon_s3'" 
									class="ui blue large circular button" 
									type="button"
									@click="amazonS3LoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="wasabiMarker && selectedDrive === 'wasabi'" 
									class="ui blue large circular button" 
									type="button"
									@click="wasabiLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="oneDriveNextLink && selectedDrive === 'onedrive'" 
									class="ui blue large circular button" 
									type="button"
									@click="oneDriveLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="dropBoxCursor && selectedDrive === 'dropbox'" 
									class="ui blue large circular button"
									type="button"
									@click="dropBoxDriveLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="yandexDiskOffset && selectedDrive === 'yandex'" 
									class="ui blue large circular button"
									type="button"
									@click="yandexDiskLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button class="ui yellow large circular button"type="button" @click="closeDriveModal">{{ __('Close') }}</button>
				</div>
			</div>
		</div>
	</form>
	
	<form>
		<input type="file" name="download" data-destination="downloads" @change="uploadFileAsync" class="d-none" accept=".zip">
	</form>

	<form>
		<input type="file" name="preview" :accept="inputFileType()" data-destination="previews" @change="uploadFileAsync" class="d-none">
	</form>

	<form>
		<input type="file" name="cover" data-destination="covers" @change="uploadFileAsync" class="d-none" accept="image/*" >
	</form>

	<form>
		<input type="file" name="screenshots" data-destination="screenshots" @change="uploadFileAsync" class="d-none" accept=".zip">
	</form>

	<div id="wavesurfer" class="d-none"></div>

	<div class="ui inverted dimmer"><div class="ui text loader">{{ __('Generating and caching audio wave') }}</div></div>
</div>

<script type="application/javascript">
	'use strict';

	var app = new Vue({
  	el: '#product',
  	data: {
  		mainFilesList: {google: [], amazon_s3: [], onedrive: [], dropbox: [], yandex: [], wasabi: []},
  		selectedDrive: '{{ old('file_host', '') }}',
  		googleDriveNextPageToken: null,
  		dropBoxCursor: null,
  		oneDriveNextLink: null,
  		yandexDiskOffset: null,
  		amazonS3Marker: null,
  		wasabiMarker: null,
  		drivePageSize: 40,
  		drivesTitles: {google: 'Google Drive', amazon_s3: 'Amazon S3', onedrive: 'OneDrive', dropbox: 'DropBox', yandex: 'Yandex Disk', wasabi: 'Wasabi'},
  		searchFile: null,
  		parentFolder: null,
  		fileId: '{{ old('file_name') }}',
  		localFileName: '',
  		itemType: '{{ old('type', config('app.default_product_type') ?? '-') }}',
  		freeForLimitedTime: true,
  		ajaxRequests: {
  			download: "", 
  			cover: "", 
  			screenshots: "",
  			preview: ""
  		},
  		oldUploads: {
  			download: "{{ $download }}", 
  			cover: "{{ $cover }}", 
  			screenshots: "{{ $screenshots }}",
  			preview: "{{ $preview }}"
  		}
  	},
  	methods: {
  		browsePreviewFile: function(from)
  		{
  			$('input[name="preview_upload_link"], input[name="preview_direct_link"]').hide();

  			if(from === 'preview')
  			{
  				$('input[name="preview"]').click();
  			}
  			else
  			{
  				$('input[name="'+ (from === 'preview_upload_link' ? 'preview_direct_link' : 'preview_upload_link') +'"]').val('');

  				$('input[name="'+from+'"]').show();
  			}
  		},
  		browserMainFile: function(from)
  		{
				$('input[name="main_file_upload_link"], input[name="main_file_download_link"]').hide();

  			if(from === 'local')
  			{  				
  				$('input[name="download"]').click();
  			}
  			else if(from === 'google')
  			{
  				this.googleDriveInit();
  			}
  			else if(from === 'amazon_s3')
  			{
  				this.amazonS3Init();
  			}
  			else if(from === 'wasabi')
  			{
  				this.wasabiInit();
  			}
  			else if(from === 'onedrive')
  			{
  				this.oneDriveInit();
  			}
  			else if(from === 'dropbox')
  			{
  				this.dropboxDriveInit();
  			}
  			else if(from === 'yandex')
  			{
  				this.yandexDiskInit();
  			}

  			if(!/^main_file_(upload|download)_link$/i.test(from))
  			{
  				this.selectedDrive = from;
  			}
  			else
  			{
  				$('input[name="'+from+'"]').show();
  			}

  			if(/^(google|amazon_s3|oneDrive|dropbox|yandex|wasabi)$/i.test(from))
  			{
  				$('#files-list').modal('show')
  			}
  		},
  		googleDriveLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			if(this.googleDriveNextPageToken)
  			{
  				var payload = {
  					'files_host': 'GoogleDrive', 
						'page_size': this.drivePageSize, 
						'nextPageToken': this.googleDriveNextPageToken,
						'is_dir': $('input[name="is_dir"]').val().trim() || 0,
					};

  				$.post('{{ route('products.list_files') }}', payload, null, 'json')
  				.done(function(res)
  				{
  					if(!res.files_list)
  						return;

						app.googleDriveNextPageToken = res.files_list.nextPageToken || null;
  					
  					e.target.disabled = app.googleDriveNextPageToken ? false : true;

  					Vue.set(app.mainFilesList, 'google', 
  								  app.mainFilesList.google.concat(res.files_list.files || []));
  				})
  			}
  		},
  		amazonS3LoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			if(this.amazonS3Marker)
  			{
  				var payload = {
  					'files_host': 'AmazonS3', 
						'page_size': this.drivePageSize, 
						'marker': this.amazonS3Marker
					};

  				$.post('{{ route('products.list_files') }}', payload, null, 'json')
  				.done(function(res)
  				{
  					if(!res.files_list)
  						return;

						app.amazonS3Marker = res.files_list.marker || null;
  					e.target.disabled = res.files_list.has_more ? false : true;

  					Vue.set(app.mainFilesList, 'amazon_s3', 
  								  app.mainFilesList.amazon_s3.concat(res.files_list.files || []));
  				})
  			}
  		},
  		wasabiLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			if(this.wasabiS3Marker)
  			{
  				var payload = {
  					'files_host': 'Wasabi', 
						'page_size': this.drivePageSize, 
						'marker': this.wasabiS3Marker
					};

  				$.post('{{ route('products.list_files') }}', payload, null, 'json')
  				.done(function(res)
  				{
  					if(!res.files_list)
  						return;

						app.wasabiS3Marker = res.files_list.marker || null;
  					e.target.disabled = res.files_list.has_more ? false : true;

  					Vue.set(app.mainFilesList, 'wasabi', 
  								  app.mainFilesList.wasabi.concat(res.files_list.files || []));
  				})
  			}
  		},
  		oneDriveLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			if(this.oneDriveNextLink)
  			{
  				var payload = {
  					'files_host': 'OneDrive', 
						'page_size': this.drivePageSize, 
						'nextLink': this.oneDriveNextLink
					};

  				$.post('{{ route('products.list_files') }}', payload, null, 'json')
  				.done(function(res)
  				{
  					if(!res.files_list)
  						return;

						app.oneDriveNextLink = res.files_list.nextLink || null;
  					
  					e.target.disabled = app.oneDriveNextLink ? false : true;

  					Vue.set(app.mainFilesList, 'onedrive', 
  								  app.mainFilesList.onedrive.concat(res.files_list.files || []));
  				})
  			}
  		},
  		dropBoxDriveLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			var payload = {
  				'files_host': 'DropBox', 
	  			'cursor': this.dropBoxCursor, 
	  			'limit': this.drivePageSize,
	  			'is_dir': $('input[name="is_dir"]').val().trim() || 0,
	  		};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					if(!res.files_list)
						return;

					app.dropBoxCursor = res.files_list.has_more ? res.files_list.cursor : null;

					e.target.disabled = res.files_list.has_more ? false : true;

					Vue.set(app.mainFilesList, 'dropbox', 
  								app.mainFilesList.dropbox.concat(res.files_list.files || []));
				})
  		},
  		yandexDiskLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			var payload = {
  				'files_host': 'YandexDisk', 
	  			'offset': this.yandexDiskOffset, 
	  			'limit': this.drivePageSize
	  		};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					if(!res.files_list)
						return;

					app.yandexDiskOffset = res.files_list.offset;

					e.target.disabled = app.yandexDiskOffset === null;

					Vue.set(app.mainFilesList, 'yandex', 
  								app.mainFilesList.yandex.concat(res.files_list.items || []));
				})
  		},
  		setFolder: function()
  		{
  			if(this.selectedDrive === 'google')
  			{  				
  				this.googleDriveInit();
  			}
  			else if(this.selectedDrive === 'amazon_s3')
  			{
  				this.amazonS3Init();
  			}
  			else if(this.selectedDrive === 'wasabi')
  			{
  				this.wasabiInit();
  			}
  			else if(this.selectedDrive === 'onedrive')
  			{
  				this.oneDriveInit();
  			}
  			else if(this.selectedDrive === 'dropbox')
  			{
  				this.dropboxDriveInit();
  			}
  		},
  		googleDriveInit: function()
  		{
				var payload = {
					'files_host': 'GoogleDrive', 
					'page_size': this.drivePageSize, 
					'parent': this.parentFolder,
					'keyword': this.searchFile
				};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'google', []);
							return;
						}	
					}
					catch(error){}

					app.googleDriveNextPageToken = res.files_list.nextPageToken || null;
					
					Vue.set(app.mainFilesList, 'google', res.files_list.files);
				})
  		},
  		amazonS3Init: function()
  		{
				var payload = {
					'files_host': 'AmazonS3', 
					'page_size': this.drivePageSize, 
					'parent': this.parentFolder,
					'keyword': this.searchFile
				};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'amazon_s3', []);
							return;
						}	
					}
					catch(error){}

					app.amazonS3Marker = res.files_list.marker || null;
					
					Vue.set(app.mainFilesList, 'amazon_s3', res.files_list.files);
				})
  		},
  		wasabiInit: function()
  		{
				var payload = {
					'files_host': 'Wasabi', 
					'page_size': this.drivePageSize, 
					'parent': this.parentFolder,
					'keyword': this.searchFile
				};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'wasabi', []);
							return;
						}	
					}
					catch(error){}

					app.wasabiMarker = res.files_list.marker || null;
					
					Vue.set(app.mainFilesList, 'wasabi', res.files_list.files);
				})
  		},
  		oneDriveInit: function()
  		{
				var payload = {
					'files_host': 'OneDrive', 
					'page_size': this.drivePageSize, 
					'folder': this.parentFolder,
					'keyword': this.searchFile
				};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'onedrive', []);
							return;
						}	
					}
					catch(error){}

					app.oneDriveNextLink = res.files_list.nextLink || null;
					
					Vue.set(app.mainFilesList, 'onedrive', res.files_list.files);
				})
  		},
  		dropboxDriveInit: function()
  		{
  			var payload = {
  				'files_host': 'DropBox', 
  				'limit': this.drivePageSize,
  				'path': this.parentFolder,
  				'keyword': this.searchFile,
  			};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'dropbox', []);
							return;
						}
					}
					catch(error){}

					app.dropBoxCursor = (res.files_list || {}).hasOwnProperty('has_more') ? res.files_list.cursor : null;
  				
  				Vue.set(app.mainFilesList, 'dropbox', res.files_list.files);
				})
  		},
  		yandexDiskInit: function()
  		{
  			var payload = {
  				'files_host': 'YandexDisk', 
  				'limit': this.drivePageSize,
  				'keyword': this.searchFile
  			};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.items.length || null)
						{
							Vue.set(app.mainFilesList, 'yandex', []);
							return;
						}
					}
					catch(error){}

					app.yandexDiskOffset = (res.files_list.offset > 0) ? res.files_list.offset : null;
  				
  				Vue.set(app.mainFilesList, 'yandex', res.files_list.items);
				})
  		},
  		searchFiles: function()
  		{
  			if(this.selectedDrive === 'google')
  			{  				
  				this.googleDriveInit();
  			}
  			else if(this.selectedDrive === 'amazon_s3')
  			{
  				this.amazonS3Init();
  			}
  			else if(this.selectedDrive === 'wasabi')
  			{
  				this.wasabiInit();
  			}
  			else if(this.selectedDrive === 'onedrive')
  			{  				
  				this.oneDriveInit();
  			}
  			else if(this.selectedDrive === 'dropbox')
  			{
  				this.dropboxDriveInit();
  			}
  			else if(this.selectedDrive === 'yandex')
  			{
  				this.yandexDiskInit();
  			}
  		},
  		setSelectedFile: function(fileId)
  		{
  			this.fileId = fileId;

  			$('#files-list').modal('hide');
  		},
  		removeSelectedFile: function()
  		{
  			this.selectedDrive = '';
  			this.fileId 			 = null;

  			Vue.nextTick(function()
  			{
  				$('.ui.dropdown.files').dropdown();
  			})
  		},
  		getFileExtension(item)
  		{
  			var baseUrl = '/assets/images/';

  			if(this.selectedDrive === 'dropbox')
  			{
	  			var sufx = item.name.slice(-4);
	  			
	  			if(/\.zip/i.test(sufx))
  					baseUrl += 'zip';
  				else if(/\.rar/i.test(sufx))
  					baseUrl += 'rar';
  				else
  					baseUrl += 'file';
  			}
  			else if(/^oneDrive|google|amazon_s3|wasabi$/i.test(this.selectedDrive))
  			{
  				var mt = item.mimeType;

  				if(/zip/i.test(mt))
  					baseUrl += 'zip';
  				else if(/rar/i.test(mt))
  					baseUrl += 'rar';
  				else
  					baseUrl += 'file';
  			}
  			else if(this.selectedDrive === 'yandex')
  			{
  				var mt = item.mime_type;

  				if(/zip/i.test(mt))
  					baseUrl += 'zip';
  				else if(/rar/i.test(mt))
  					baseUrl += 'rar';
  				else
  					baseUrl += 'file';
  			}

  			return baseUrl + '.png';
  		},
  		closeDriveModal: function()
  		{
  			$('#files-list').modal('hide')
  		},
  		setDefaultDrive: function()
  		{
  			this.selectedDrive = 'local';
  		},
  		selectFile: function(name)
  		{
  			$('input[name="'+name+'"]').click()
  		},
  		abortUpload: function(name)
  		{
  			this.ajaxRequests[name].abort();

  			Vue.set(app.ajaxRequests, name, {});

  			$('input[name="'+name+'"]').closest('form')[0].reset()
  		},
  		removeUploadedFile: function(name)
  		{
  			$.post('{{ route('products.delete_file_async') }}', {path: this.ajaxRequests[name].file_path})
  			.done(function()
  			{
  				$('input[name="'+name+'"]').closest('form')[0].reset();

  				Vue.set(app.ajaxRequests, name, {});
  			})
  			.always(function()
  			{
  				if(/^download|preview$/i.test(name.toLowerCase()))
  				{
  					Vue.nextTick(function()
  					{
  						$('.dropdown.'+name.toLowerCase()).dropdown()
  					})
  				}
  			})
  		},
  		deleteExistingFile: function(path, name)
  		{
  			$.post('{{ route('products.delete_file_async') }}', {path: path})
  			.done(function()
  			{
  				Vue.set(app.oldUploads, name, '');
  			})
  			.always(function()
  			{
  				if(/^download|preview$/i.test(name.toLowerCase()))
  				{
  					Vue.nextTick(function()
  					{
  						$('.dropdown.'+name.toLowerCase()).dropdown()
  					})
  				}
  			})
  		},
  		uploadFileAsync: function(e)
  		{
  			var file = e.target;
  			var name = file.name;

				Vue.set(app.ajaxRequests, name, {});

  			var destination = file.getAttribute('data-destination');

  			var formData = new FormData();

				formData.append('file', file.files[0]);
				formData.append('destination', destination);
				formData.append('type', this.itemType);

				var ajaxRequests = this.ajaxRequests;

	  		ajaxRequests[name] = $.ajax({
            url: '{{ route('products.upload_file_async') }}',
            xhr: function()
            {
            	var xhr = new window.XMLHttpRequest();

            	Vue.set(app.ajaxRequests[name], 'progress', 0);

            	xhr.upload.addEventListener('progress', function(event)
            	{
            		if(event.lengthComputable)
            		{
            			var complete = Number((event.loaded / event.total) * 100).toFixed();

            			Vue.set(app.ajaxRequests[name], 'progress', complete);
            		}
            	}, false);

            	return xhr;
            },
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            cache: true,
            beforeSend: function()
            {

            },
            success: function(response)
            {
              if(response.status === 'success')
              { 
              	Vue.set(app.ajaxRequests[name], 'file_name', response.file_name);
              	Vue.set(app.ajaxRequests[name], 'file_path', response.file_path);
              }
            },
            error: function()
            {

            }
        });

        this.ajaxRequests = ajaxRequests;
  		},
  		deleteFileAsync: function(path)
  		{
  			$.post('{{ route('products.delete_file_async') }}', {path: path})
  			.done(function()
  			{
  				
  			})
  		},
  		hasProgress: function(name)
  		{
  			if(this.ajaxRequests.hasOwnProperty(name))
  			{
  				return this.ajaxRequests[name].hasOwnProperty('progress');
  			}
  			
  			return false;
  		},
  		uploadInProgress: function(name)
  		{
  			if(this.hasProgress(name))
  			{
  				var progress = this.ajaxRequests[name].progress;

  				return (progress == 0 || progress <= 100) && !this.ajaxRequests[name].hasOwnProperty('file_name');
  			}

  			return false;
  		},
  		finishedUploading: function(name)
  		{
  			if(this.hasProgress(name))
  			{
  				return this.ajaxRequests[name].progress == 100;
  			}

  			return false;
  		},
  		inputIsOff: function(name)
  		{
  			return this.uploadInProgress(name) || this.finishedUploading(name);
  		},
  		anyInputOff: function()
  		{
  			var inputs = ['download', 'cover', 'screenshots', 'preview'];
  			var app 	 = this;

  			for(var k = 0; k < inputs.length; k++)
  			{
  				if(this.uploadInProgress(inputs[k]))
  					return true;
  			}

  			return false;
  		},
  		setItemType: function(e)
  		{
  			this.itemType = e.target.value;
  		},
  		inputFileType: function()
  		{
  			var types = {
  				'ebook'		: '.pdf',
  				'audio'		: '.mp3',
  				'video'   : '.mp4',
  				'graphic' : '*',
  				'external_membership': '*',
  				'-'				: '*'
  			};
  			
  			return types[this.itemType] || '*';
  		},
  		setPreviewType: function(val)
  		{
  			var previewType = (val === 'audio') ? 'audio' : (val === 'video') ? 'video' : (val === 'ebook') ? 'pdf' : (val === 'graphic') ? 'zip' : 'other';

  			$('input[name="preview_type"]').val(previewType).closest('.ui.dropdown').dropdown();
  		}
  	},
  	watch: {
  		itemType: function(val)
  		{
  			Vue.nextTick(function()
  			{
  				app.setPreviewType(val);
  			})
  		}
  	},
  	mounted: function()
  	{
  		this.setPreviewType(this.itemType);
  	}
  })



	function savePeaks(previewUrl, filename = null)
	{
		var wSuffer = WaveSurfer.create({
			    container: $('#wavesurfer')[0],
			    responsive: true,
			    partialRender: true,
			    waveColor: '#D9DCFF',
			    progressColor: '#4353FF',
			    cursorColor: '#4353FF',
			    barWidth: 2,
			    barRadius: 3,
			    cursorWidth: 1,
			    height: 60,
			    barGap: 2
			});

			wSuffer.once('ready', () => 
			{
					wSuffer.exportPCM(1024, 10000, true).then(function(res)
					{
						$.post("{{ route('products.save_wave') }}", { filename: filename, peaks: res, id: '{{ $product_id }}' })
						.always(function()
						{
							$('.ui.inverted.dimmer').toggleClass('active', false);

							$('#submit').click();
						})
					})
	    });

			wSuffer.load(previewUrl);
	}


	function savePeaksFromTempUrl(url)
	{
		$('.ui.inverted.dimmer').toggleClass('active', true);

		$.post('{{ route('products.get_temp_url') }}', {url: url, id: '{{ $product_id }}'})
		.done(function(tempUrl)
		{
			savePeaks(tempUrl, tempUrl.split('/').pop());
		})
	}


	$(function()
  {
  	@if(config('app.products_by_country_city'))
			var countriesCities = @json(config('app.countries_cities'));

	  	$('.ui.dropdown.countries').dropdown({
	  		onChange: function(value, text, $choice)
	  		{
	  			$('.ui.dropdown.cities').dropdown({
	  				values: countriesCities[value].sort().map(function(city)
	  				{
	  					return {value: city, name: city};
	  				}).concat({value: '', name: '&nbsp;'})
	  			})
	  		}
	  	})

	  	@if($country = old('country_city.country'))
			$('.ui.dropdown.cities').dropdown({
				values: countriesCities['{{ $country }}'].sort().map(function(city)
				{
					return {value: city, name: city};
				}).concat({value: '', name: '&nbsp;'})
			})

			@if($city = old('country_city.city'))
			$('.ui.dropdown.cities').dropdown('set selected', '{{ $city }}');
			@endif
	  	@endif
  	@endif

  	$('.summernote').summernote({
	    placeholder: '...',
	    tabsize: 2,
	    height: 350,
	    tooltip: false
	  });
	  	

	  $('#product .tabs .menu .item')
	  .tab({
	    context: 'parent'
	  })


		$('input[name="category"]').on('change', function()
		{
			setSubcategories($(this).val());
		})


		function setSubcategories(parentId = null, selectedValues = '')
		{
			var subcategories = @json($category_children ?? (object)[]);

			if(!isNaN(parentId))
			{
				var values = [];

				if(Object.keys(subcategories).length)
				{
					if(!subcategories.hasOwnProperty(parentId))
						return;

					for(var k in (subcategories[parentId] || []))
					{
						var subcategory = subcategories[parentId][k];

						values.push({name: subcategory.name, value: subcategory.id});
					}	
				}

				$('#subcategories').dropdown('clear').dropdown({values: values});

				if(selectedValues.length)
				{
					$('input[name="subcategories"]').val(selectedValues);
					
					$('#subcategories').dropdown();
				}
			}
		}


		@if(old('category'))
		{
			setSubcategories({{ old('category') }}, '{{ old('subcategories') }}');
		}
		@endif


		$('#files-list').modal({
			closable: false
		})


		$('input[name="download"]').on('change', function()
		{
			app.fileId = null;
			app.localFileName = $(this)[0].files[0].name;
		})


		$('#save').on('click', function(e)
		{
			if($('input[name="type"]').val() === 'audio')
			{
				var preview_upload_link = $('input[name="preview_upload_link"]').val() || '';
				var preview_direct_link = $('input[name="preview_direct_link"]').val() || '';

				if(preview_upload_link.length || preview_direct_link.length)
				{
					savePeaksFromTempUrl(preview_upload_link.length ? preview_upload_link : preview_direct_link);
				}
				else if($('input[name="preview"]')[0].files.length)
				{
					$('.ui.inverted.dimmer').toggleClass('active', true);

					savePeaks(URL.createObjectURL($('input[name="preview"]')[0].files[0]));
				}
				else
				{
					$('#submit').click()
				}
			}
			else
			{
				$('#submit').click()
			}
		})

		$(document).on('keydown', '#product input', function(e)
		{
			if(e.keyCode == 13)
			{
				e.preventDefault();
				return false;
			}
		})
  })
</script>

@endsection