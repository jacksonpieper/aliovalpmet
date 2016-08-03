# Use templavoila's wizard instead the default create new page wizard

mod.web_list.newPageWiz.overrideWithExtension = templavoila
mod.web_list.newContentWiz.overrideWithExtension = templavoila
mod.web_txtemplavoilaM2.templatePath = templates,default/templates
mod.web_txtemplavoilaM1.enableDeleteIconForLocalElements = 0
mod.web_txtemplavoilaM1.enableContentAccessWarning = 1
mod.web_txtemplavoilaM1.enableLocalizationLinkForFCEs = 0
mod.web_txtemplavoilaM1.useLiveWorkspaceForReferenceListUpdates = 1
mod.web_txtemplavoilaM1.adminOnlyPageStructureInheritance = fallback

templavoila.wizards.newContentElement.wizardItems {
	common.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common
	common.elements {
		header {
			icon = content-header
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_header_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_header_description
			tt_content_defValues {
				CType = header
			}
		}
		text {
			icon = content-text
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_regularText_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_regularText_description
			tt_content_defValues {
				CType = text
			}
		}
		textpic {
			icon = content-textpic
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_textImage_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_textImage_description
			tt_content_defValues {
				CType = textpic
				imageorient = 17
			}
		}
		image {
			icon = content-image
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_imagesOnly_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_imagesOnly_description
			tt_content_defValues {
				CType = image
				imagecols = 2
			}
		}
		bullets {
			icon = content-bullets
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_bulletList_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_bulletList_description
			tt_content_defValues {
				CType = bullets
			}
		}
		table {
			icon = content-table
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_table_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:common_table_description
			tt_content_defValues {
				CType = table
			}
		}

	}
	common.show := addToList(header,text,textpic,image,bullets,table)

	special.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special
	special.elements {
		uploads {
			icon = content-special-uploads
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_filelinks_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_filelinks_description
			tt_content_defValues {
				CType = uploads
			}
		}
		multimedia {
			icon = gfx/c_wiz/multimedia.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_multimedia_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_multimedia_description
			tt_content_defValues {
				CType = multimedia
			}
		}
		menu {
			icon = content-special-menu
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_sitemap_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_sitemap_description
			tt_content_defValues {
				CType = menu
				menu_type = 2
			}
		}
		html {
			icon = content-special-html
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_plainHTML_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_plainHTML_description
			tt_content_defValues {
				CType = html
			}
		}
		div {
		 	icon = content-special-div
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_divider_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:special_divider_description
			tt_content_defValues {
				CType = div
			}
		}

	}
	special.show := addToList(uploads,multimedia,menu,html,div)

	forms.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms
	forms.elements {
		mailform {
			icon = content-elements-mailform
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_mail_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_mail_description
			tt_content_defValues {
				CType = mailform
				bodytext (
# Example content:
Name: | *name = input,40 | Enter your name here
Email: | *email=input,40 |
Address: | address=textarea,40,5 |
Contact me: | tv=check | 1

|formtype_mail = submit | Send form!
|html_enabled=hidden | 1
|subject=hidden| This is the subject
				)
			}
		}
		search {
			icon = content-elements-search
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_search_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_search_description
			tt_content_defValues {
				CType = search
			}
		}
		login {
			icon = content-elements-login
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_login_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:forms_login_description
			tt_content_defValues {
				CType = login
			}
		}

	}
	forms.show := addToList(mailform,search,login)

	fce.header = LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xlf:fce
	fce.elements  {

	}
	fce.show = *

	plugins.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:plugins
	plugins.elements {
		general {
			icon = content-plugin
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:plugins_general_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xlf:plugins_general_description
			tt_content_defValues.CType = list
		}
	}
	plugins.show = *
}
# set to tabs for tab rendering
templavoila.wizards.newContentElement.renderMode =