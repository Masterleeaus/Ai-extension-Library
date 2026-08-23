import 'package:cached_network_image/cached_network_image.dart';
import 'package:liquid_swipe/liquid_swipe.dart';

import '../../backend/services/api_endpoint.dart';
import '../../controller/app_settings/app_settings_controller.dart';
import '../../controller/splsh_onboard/onboard_controller.dart';
import '/backend/local_storage/local_storage.dart';
import '/routes/routes.dart';
import '/utils/basic_screen_imports.dart';
import '/utils/responsive_layout.dart';

class OnboardScreen extends StatelessWidget {
  OnboardScreen({super.key});
  final controller = Get.put(OnBoardController());
  final appSettingsController = Get.find<AppSettingsController>();

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        body: LiquidSwipe(
          waveType: WaveType.liquidReveal,
          fullTransitionValue: 880,
          enableSideReveal: true,
          preferDragFromRevealedArea: true,
          // enableLoop: true,
          ignoreUserGestureWhileAnimating: true,
          positionSlideIcon: 0.55,
          slideIconWidget: Container(
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(),
            ),
            child: Padding(
              padding: EdgeInsets.all(Dimensions.paddingSize * 0.2),
              child: const Icon(Icons.arrow_forward_ios_outlined),
            ),
          ),
          pages: appSettingsController.onboardScreen.map(
            (data) {
              return Container(
                color: Colors.white,
                child: Stack(
                  children: [
                    Positioned(
                      top: 10,
                      child: CachedNetworkImage(
                        height: MediaQuery.of(context).size.height,
                        imageUrl:
                            "${ApiEndpoint.mainDomain}/${appSettingsController.appSettingsModel.data.screenImagePath}/${data.image}",
                        placeholder: (context, url) => Container(),
                        errorWidget: (context, url, error) => Container(),
                        fit: BoxFit.cover,
                      ),
                    ),
                    _appBarWidget(context),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        _titleAndSubTitleWidget(context, data, controller),
                        verticalSpace(Dimensions.heightSize * 1.6),
                        Obx(() => controller.dotWidget()),
                        Container(
                          padding: EdgeInsets.all(
                            Dimensions.paddingSize * 0.4,
                          ),
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: CustomColor.transparent,
                            ),
                          ),
                          margin: EdgeInsets.symmetric(
                            vertical: Dimensions.marginSizeVertical * 2.5,
                          ),
                        )
                      ],
                    ),
                  ],
                ),
              );
            },
          ).toList(),
          currentUpdateTypeCallback: (v) {
            if (controller.selectedIndex.value ==
                appSettingsController.onboardScreen.length - 1) {
              controller.pageNavigate();
            }
          },
          onPageChangeCallback: (v) {
            controller.selectedIndex.value = v;
          },
        ),
      ),
    );
  }

  _appBarWidget(BuildContext context) {
    return AppBar(
      elevation: 0,
      scrolledUnderElevation: 0,
      backgroundColor: Colors.transparent,
      title: TitleHeading3Widget(
        text: Strings.appName,
      ),
      actions: [
        _skipButtonWidget(context),
        horizontalSpace(Dimensions.widthSize),
      ],
    );
  }
}

_titleAndSubTitleWidget(BuildContext context, data, controller) {
  return Obx(
    () => Container(
      margin: EdgeInsets.symmetric(
        horizontal: Dimensions.marginSizeHorizontal * 0.8,
      ),
      child: Column(
        children: [
          Builder(builder: (context) {
            return CustomTitleHeadingWidget(
              text: data.title,
              textAlign: TextAlign.start,
              maxLines: 2,
              textOverflow: TextOverflow.ellipsis,
              style: CustomStyle.onboardTitleStyle.copyWith(
                color:
                    controller.selectedIndex.value != 0 ? Colors.white : null,
              ),
            );
          }),
          verticalSpace(Dimensions.heightSize),
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize * 0.04),
            child: CustomTitleHeadingWidget(
              text: data.subTitle,
              textAlign: TextAlign.justify,
              maxLines: 3,
              textOverflow: TextOverflow.ellipsis,
              style: CustomStyle.onboardSubTitleStyle.copyWith(
                color:
                    controller.selectedIndex.value != 0 ? Colors.white : null,
              ),
            ),
          ),
        ],
      ),
    ),
  );
}

_skipButtonWidget(BuildContext context) {
  return InkWell(
    onTap: (() {
      Get.toNamed(Routes.signInScreen);
      LocalStorages.saveOnboardDoneOrNot(isOnBoardDone: true);
    }),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        CustomTitleHeadingWidget(
          text: Strings.skip,
          style: CustomStyle.onboardSkipStyle.copyWith(
            color: CustomColor.blackColor,
          ),
        ),
      ],
    ),
  );
}
