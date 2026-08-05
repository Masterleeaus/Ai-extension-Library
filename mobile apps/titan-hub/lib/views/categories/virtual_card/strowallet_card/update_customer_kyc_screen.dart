import 'package:qrpay/backend/utils/custom_loading_api.dart';

import '../../../../controller/categories/virtual_card/strowallet_card/update_customer_kyc_controller.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../widgets/appbar/appbar_widget.dart';
import '../../../../widgets/inputs/image_widget.dart';

class UpdateCustomerKycScreen extends StatelessWidget {
  UpdateCustomerKycScreen({super.key});
  final controller = Get.put(UpdateCustomerKycController());
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarWidget(
        text: Strings.updateCustomerKyc,
      ),
      body: Obx(
        () => controller.isCreateCardInfoLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingHorizontalSize,
      ),
      child: Form(
        child: ListView(
          children: [
            verticalSpace(Dimensions.heightSize),
            PrimaryInputWidget(
              hint: Strings.enterFirstName,
              label: Strings.firstName,
              controller: controller.firstNameController,
              optionalLabel:
                  "(*${controller.strowalletCardCreateInfo.data.customerCreateFields[0].siteLabel})",
            ),
            verticalSpace(Dimensions.heightSize),
            PrimaryInputWidget(
              hint: Strings.enterLastName,
              label: Strings.lastName,
              controller: controller.lastNameController,
              optionalLabel:
                  "(*${controller.strowalletCardCreateInfo.data.customerCreateFields[1].siteLabel})",
            ),
            verticalSpace(Dimensions.marginSizeVertical * 0.5),
            ImageWidget(
                labelName: controller.strowalletCardCreateInfo.data
                    .customerCreateFields[10].labelName,
                fieldName: 'user_image',
                optionalLabel:
                    "(${controller.strowalletCardCreateInfo.data.customerCreateFields[10].siteLabel})"),
            verticalSpace(Dimensions.marginSizeVertical * 0.5),
            ImageWidget(
              labelName: controller.strowalletCardCreateInfo.data
                  .customerCreateFields[9].labelName,
              fieldName: 'id_image_font',
              optionalLabel:
                  "(${controller.strowalletCardCreateInfo.data.customerCreateFields[9].siteLabel})",
            ),
            verticalSpace(Dimensions.marginSizeVertical * 0.7),
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: crossStart,
                    children: [
                      TitleHeading4Widget(
                        text: controller.strowalletCardCreateInfo.data
                            .customerCreateFields[9].labelName,
                      ),
                      verticalSpace(Dimensions.marginBetweenInputTitleAndBox),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(Dimensions.radius),
                        child: Image.network(
                          controller.idImage.value,
                          height: Dimensions.heightSize * 8,
                        ),
                      ),
                    ],
                  ),
                ),
                horizontalSpace(Dimensions.widthSize * 1.3),
                Expanded(
                  child: Column(
                    crossAxisAlignment: crossStart,
                    children: [
                      TitleHeading4Widget(
                        text: controller.strowalletCardCreateInfo.data
                            .customerCreateFields[10].labelName,
                      ),
                      verticalSpace(Dimensions.marginBetweenInputTitleAndBox),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(Dimensions.radius),
                        child: Image.network(
                          controller.userPhoto.value,
                          height: Dimensions.heightSize * 8,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            verticalSpace(Dimensions.heightSize * 2),
            Obx(
              () => controller.isConfirmLoading
                  ? const CustomLoadingAPI()
                  : PrimaryButton(
                      title: Strings.submit,
                      onPressed: () {
                        controller.updateCustomerKyc().then(
                          (v) {
                            Get.close(1);
                          },
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
