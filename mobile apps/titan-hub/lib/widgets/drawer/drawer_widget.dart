import 'dart:io';

import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../backend/services/api_endpoint.dart';
import '../../controller/others/log_out_controller.dart';
import '../../controller/profile/update_profile_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../utils/drawer_utils.dart';
import '../../views/others/custom_image_widget.dart';

class CustomDrawer extends StatelessWidget {
  CustomDrawer({super.key});
  final controller = Get.put(UpdateProfileController());
  final logOutController = Get.put(LogOutController());

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Drawer(
        width: MediaQuery.of(context).size.width / 1.34,
        shape: Platform.isAndroid
            ? RoundedRectangleBorder(
                borderRadius: BorderRadius.only(
                topRight: Radius.circular(
                  Dimensions.radius * 2,
                ),
              ))
            : RoundedRectangleBorder(
                borderRadius: BorderRadius.only(
                topRight: Radius.circular(
                  Dimensions.radius * 2,
                ),
                bottomRight: Radius.circular(
                  Dimensions.radius * 2,
                ),
              )),
        backgroundColor: Get.isDarkMode
            ? CustomColor.primaryBGDarkColor
            : CustomColor.whiteColor,
        child: controller.isLoading
            ? const CustomLoadingAPI()
            : ListView(
                children: [
                  _userImgWidget(context),
                  _userTextWidget(context),
                  _drawerWidget(context),
                ],
              ),
      ),
    );
  }

  _userImgWidget(BuildContext context) {
    var data = controller.profileModel.data;

    final image =
        '${ApiEndpoint.mainDomain}/${data.imagePath}/${data.user.image}';
    final defaultImage =
        '${ApiEndpoint.mainDomain}/${data.imagePath}/${data.defaultImage}';
    return Center(
      child: Container(
        margin: EdgeInsets.only(
          top: Dimensions.paddingSize,
          bottom: Dimensions.paddingSize,
        ),
        height: Dimensions.heightSize * 8.3,
        width: Dimensions.widthSize * 11.5,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
          color: Colors.transparent,
          border: Border.all(color: CustomColor.primaryLightColor, width: 5),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(Dimensions.radius),
          child: FadeInImage(
            height: double.infinity,
            width: double.infinity,
            fit: BoxFit.cover,
            image:
                NetworkImage(data.user.image.isNotEmpty ? image : defaultImage),
            placeholder: AssetImage(
              Assets.clipart.user.path,
            ),
            imageErrorBuilder: (context, error, stackTrace) {
              return Image.asset(
                Assets.clipart.user.path,
                height: double.infinity,
                width: double.infinity,
                fit: BoxFit.cover,
              );
            },
          ),
        ),
      ),
    );
  }

  _userTextWidget(BuildContext context) {
    var data = controller.profileModel.data;
    return Column(
      children: [
        TitleHeading3Widget(
          text: data.user.username,
          fontSize: Dimensions.headingTextSize2,
        ),
        FittedBox(
          child: Padding(
            padding: EdgeInsets.symmetric(
              horizontal: Dimensions.marginSizeHorizontal * 0.5,
            ),
            child: TitleHeading4Widget(
              text: data.user.email,
              fontWeight: FontWeight.w500,
              color: CustomColor.primaryLightColor,
              fontSize: Dimensions.headingTextSize3,
            ),
          ),
        ),
        verticalSpace(Dimensions.heightSize * 2)
      ],
    );
  }
 
  _drawerWidget(BuildContext context) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return Column(
      crossAxisAlignment: crossStart,
      mainAxisAlignment: mainCenter,
      children: [
        ...DrawerUtils.items.map((item) {
          return _drawerTileWidget(
            context,
            item['icon'],
            item['title'],
            onTap: () {
              Navigator.pop(context);
              Get.toNamed(
                '${item['route']}',
              );
            },
          );
        }),
        InkWell(
          onTap: () {
            _logOutDialogueWidget(context);
          },
          child: Padding(
            padding: EdgeInsets.symmetric(
              horizontal: Dimensions.paddingSize * (!isTablet() ? 1 : 1.34),
              vertical: Dimensions.paddingSize * 0.2,
            ),
            child: Row(
              crossAxisAlignment: crossCenter,
              mainAxisAlignment: mainStart,
              children: [
                Container(
                  alignment: Alignment.center,
                  height: Dimensions.heightSize * (isTablet() ? 2.5 : 2.2),
                  width: Dimensions.widthSize * (isTablet() ? 3.3 : 3),
                  decoration: BoxDecoration(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.7),
                    color: CustomColor.primaryLightColor,
                  ),
                  child: CustomImageWidget(
                    path: Assets.icon.signout,
                    height: Dimensions.heightSize * (isTablet() ? 2.5 : 2.2),
                    width: Dimensions.widthSize * (isTablet() ? 3.3 : 3),
                  ),
                ),
                horizontalSpace(Dimensions.widthSize),
                TitleHeading3Widget(
                  text: Strings.signOut,
                  color: Get.isDarkMode
                      ? CustomColor.primaryDarkTextColor
                      : CustomColor.primaryLightTextColor,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  _drawerTileWidget(context, icon, title, {required VoidCallback onTap}) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSize * 1,
          vertical: Dimensions.paddingSize * 0.2,
        ),
        child: Row(
          crossAxisAlignment: crossCenter,
          mainAxisAlignment: mainStart,
          children: [
            Container(
              alignment: Alignment.center,
              height: Dimensions.heightSize * (isTablet() ? 2.5 : 2.2),
              width: Dimensions.widthSize * (isTablet() ? 3.3 : 3),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(Dimensions.radius * 0.7),
                color: CustomColor.primaryLightColor,
              ),
              child: CustomImageWidget(
                path: icon,
                height: Dimensions.heightSize * 3,
                width: Dimensions.widthSize * 3.6,
              ),
            ),
            horizontalSpace(Dimensions.widthSize),
            Expanded(
              child: TitleHeading3Widget(
                text: title,
                maxLines: 2,
              ),
            ),
          ],
        ),
      ),
    );
  }

  _logOutDialogueWidget(
    BuildContext context,
  ) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          backgroundColor: Colors.transparent,
          alignment: Alignment.center,
          insetPadding: EdgeInsets.all(Dimensions.paddingSize * 0.3),
          contentPadding: EdgeInsets.zero,
          clipBehavior: Clip.antiAliasWithSaveLayer,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          content: Builder(
            builder: (context) {
              var width = MediaQuery.of(context).size.width;
              return Container(
                width: width * 0.84,
                margin: EdgeInsets.all(Dimensions.paddingSize * 0.5),
                padding: EdgeInsets.all(Dimensions.paddingSize * 0.9),
                decoration: BoxDecoration(
                  color: Get.isDarkMode
                      ? CustomColor.primaryBGDarkColor
                      : CustomColor.whiteColor,
                  borderRadius: BorderRadius.circular(Dimensions.radius * 1.4),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: crossCenter,
                  children: [
                    SizedBox(height: Dimensions.heightSize * 2),
                    TitleHeading2Widget(text: Strings.signOut.tr),
                    verticalSpace(Dimensions.heightSize * 1),
                    TitleHeading4Widget(text: Strings.logMessage.tr),
                    verticalSpace(Dimensions.heightSize * 1),
                    Row(
                      children: [
                        Expanded(
                          child: SizedBox(
                            width: MediaQuery.of(context).size.width * .25,
                            child: PrimaryButton(
                              title: Strings.cancel.tr,
                              onPressed: () {
                                Get.back();
                              },
                              borderColor: CustomColor.blackColor,
                              buttonColor: CustomColor.blackColor,
                            ),
                          ),
                        ),
                        horizontalSpace(Dimensions.widthSize),
                        Expanded(
                          child: SizedBox(
                            width: MediaQuery.of(context).size.width * .25,
                            child: Obx(
                              () => logOutController.isLoading
                                  ? const CustomLoadingAPI()
                                  : PrimaryButton(
                                      title: Strings.signOut.tr,
                                      onPressed: () {
                                        logOutController.logOutProcess();
                                      },
                                      borderColor:
                                          CustomColor.primaryLightColor,
                                    ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              );
            },
          ),
        );
      },
    );
  }
}
